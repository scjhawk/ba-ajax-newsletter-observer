<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition End User License Agreement
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magento.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    BlueAcorn
 * @package     BlueAcorn_AjaxNewsletter
 * @copyright Copyright (c) 2006-2015 X.commerce, Inc. (http://www.magento.com)
 * @license http://www.magento.com/license/enterprise-edition
 */

/**
 * AjaxNewsletter observer model
 *
 * @category    BlueAcorn
 * @package     BlueAcorn_AjaxNewsletter
 * @author      Grant Wimmer <grant.wimmer@blueacorn.com>
 */
class BlueAcorn_AjaxNewsletter_Model_Observer
{
    public function processSubscription(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfig('ajaxnewsoptions/ajaxsubmit/enabled'))
        {
            $request = Mage::app()->getRequest();
            $action = $request->getActionName();
            Mage::app()->getFrontController()->getAction()->setFlag($action, Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);

            $controller = $observer->getEvent()->getControllerAction();

            if (Mage::app()->getRequest()->getParam('is-ajax')) {
                $submittedEmail = $request->getPost('email');

                try {
                    // Check if the email is valid
                    if (!Zend_Validate::is($submittedEmail, 'EmailAddress')) {
                        Mage::throwException($controller->__("Invalid address '{$submittedEmail}'. Please enter a valid email address."));
                    }
                    // Check if guests are allowed to subscribe to the newsletter (if the user is not logged in)
                    if (Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG) != 1 &&
                        !$customerSession->isLoggedIn()
                    ) {
                        Mage::throwException($controller->__('Sorry, but administrator denied subscription for guests. Please <a href="%s">register</a>.', Mage::helper('customer')->getRegisterUrl()));
                    }
                    // Check if the submitted email is already assigned to another user
                    $ownerId = Mage::getModel('customer/customer')
                        ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                        ->loadByEmail($submittedEmail)
                        ->getId();
                    if ($ownerId !== null && $ownerId != $customerSession->getId()) {
                        Mage::throwException($controller->__("The address '{$submittedEmail}' is already assigned to another user."));
                    }
                    // Check if email is already in the subscriber table
                    $newsletter = Mage::getModel('newsletter/subscriber');

                    if ($newsletter->loadByEmail($submittedEmail)->getId()) {
                        Mage::throwException($controller->__("The address '{$submittedEmail}' is already subscribed to our newsletter."));
                    }
                    // If there are no errors with the email, register it for the newsletter
                    $status = Mage::getModel('newsletter/subscriber')->subscribe($submittedEmail);

                    if ($status == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE) {
                        $response['message'] = $controller->__('Confirmation request has been sent.');
                    } else {
                        $response['message'] = $controller->__("The address '{$submittedEmail}' has been subscribed to our newsletter. Thank you!");
                    }
                    $response['status'] = 'success';
                } catch (Exception $e) {
                    $response['message'] = $e->getMessage();
                    $response['status'] = "error";
                }

                // Encode the subscription status and message as JSON and return to the AJAX request for display
                Mage::app()->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
            }
        }
    }
}