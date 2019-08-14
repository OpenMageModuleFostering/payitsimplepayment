<?php

class PayItSimple_Payment_Block_Form_Pis extends Mage_Payment_Block_Form_Cc
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payitsimple/form/pis.phtml');
    }

    public function getAvailableInstallments()
    {
        $method = $this->getMethod();
        $installments = array();
        $totalAmount = Mage::getSingleton('checkout/session')->getQuote()->getGrandTotal();
        $options = Mage::getModel('pis_payment/source_installments')->toOptionArray();
        foreach (explode(',', $method->getConfigData('available_installments')) as $n) {
            if (isset($options[$n]['label'])) $installments[$n] = $options[$n]['label'] .' '. $this->__('of') . ' ' . $this->helper('checkout')->formatPrice(round($totalAmount/$n,2));
        }
        return $installments;
    }

    public function getMethodLabelAfterHtml(){
        $markFaq = Mage::getConfig()->getBlockClassName('core/template');
        $markFaq = new $markFaq;
        $markFaq->setTemplate('payitsimple/form/method_faq.phtml')
            ->setPaymentInfoEnabled($this->getMethod()->getConfigData('faq_link_enabled'))
            ->setPaymentInfoTitle($this->getMethod()->getConfigData('faq_link_title'));
        return $markFaq->toHtml();
    }
}
