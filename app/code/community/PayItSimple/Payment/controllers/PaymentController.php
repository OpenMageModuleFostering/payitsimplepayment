<?php

class PayItSimple_Payment_PaymentController extends Mage_Core_Controller_Front_Action
{
    public function helpAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function termsAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
}