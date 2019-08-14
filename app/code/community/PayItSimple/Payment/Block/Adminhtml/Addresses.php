<?php
class PayItSimple_Payment_Block_Adminhtml_Addresses extends Mage_Adminhtml_Block_System_Config_Form_Field
{
   protected $_addRowButtonHtml = array();
   protected $_removeRowButtonHtml = array();
 public function __construct()
    {
         parent::_construct();
        //$this->setTemplate('payitsimple/system/config/button.phtml');
    }

    protected function _prepareLayout()
    {
       /* $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'label' => Mage::helper('catalog')->__('Add Tier'),
                'onclick' => 'return tierPriceControl.addItem()',
                'class' => 'add'
            ));
        $button->setName('add_tier_price_item_button');

        $this->setChild('add_button', $button);
        return parent::_prepareLayout();*/
    }
   /**
    * Returns html part of the setting
    *
    * @param Varien_Data_Form_Element_Abstract $element
    * @return string
    */
   protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
   {
      $this->setElement($element);
      $doctv = Mage::getStoreConfig('payment/pis_cc/depanding_on_cart_total_values'); 
      $html = "";
      if($doctv == ""){
        $html = $this->getTableHtmlWhenEmpty();
      }else{
        $html = $this->getTableHtmlWhenNotEmpty($doctv);
      }


       return $html; 
   }

   // return html when there is not prior configuration is set for Depending on cart total
   protected function getTableHtmlWhenEmpty()
   {
      $html = '<table class="data border splitit" id="tiers_table" cellspacing="0" border="1">
      <div class="tiers_table_overlay"></div>
         <colgroup>
            <col width="120">
            <col width="95">
            <col>
            <col width="1">
         </colgroup>
         <thead>
            <tr class="headings">
               <th style="display:none">Website</th>
               <th>Cart total</th>
               <th>#Installments</th>
               <th>Currency</th>
               <th class="last">Action</th>
            </tr>
         </thead>
         <tbody id="tier_price_container">
            <tr>
               <td>
                From<br><label>'. $this->_getFirstAvailableCurrencySymbol() .'</label> <input style="max-width:90%!important;" type="text" class="doctv_from" name="doctv_from" /><br>To<br><label>'. $this->_getFirstAvailableCurrencySymbol() .'<input type="text" style="max-width:90%!important;" name="doctv_to" class="doctv_to" />
               </td>
               <td>
                <select id="" name="doctv_installments" class=" select multiselect doctv_installments" size="10" multiple="multiple">
                  <option value="2">2 Installments</option>
                  <option value="3">3 Installments</option>
                  <option value="4">4 Installments</option>
                  <option value="5">5 Installments</option>
                  <option value="6">6 Installments</option>
                  <option value="7">7 Installments</option>
                  <option value="8">8 Installments</option>
                  <option value="9">9 Installments</option>
                  <option value="10">10 Installments</option>
                  <option value="11">11 Installments</option>
                  <option value="12">12 Installments</option>
                  </select>
               </td>
               <td>
                <select id="" name="doctv_currency" class=" select doctv_currency">
                  '.$this->_getCurrencies().'
                 
               </td>
               <td>
                <button title="Delete Tier" type="button" class="scalable delete icon-btn delete-product-option" id="" onclick="deleteRow(this);"><span><span><span>Delete</span></span></span></button>
               </td>
            </tr>
         </tbody>
         <tfoot>
            <tr>
               <td style="display:none"></td>
               <td colspan="4" class="a-right"><button id="id_e33ad31ef8ac28bb6e4a4fd4d54f5f9e" title="Add Tier" type="button" class="scalable add" onclick="addRow();" style=""><span><span><span>Add Tier</span></span></span></button></td>
            </tr>
         </tfoot>
      </table>'; 
      return $html;
   }

   // return html when there is prior configuration is set for Depending on cart total
   protected function getTableHtmlWhenNotEmpty($doctv)
   {
      $doctv = json_decode($doctv);
      $currencySymbolsArray = json_decode($this->_getAvailableCurrencySymbolsArray(),true);

      $html = '<table class="data border splitit" id="tiers_table" cellspacing="0" border="1">
      <div class="tiers_table_overlay"></div>
         <colgroup>
            <col width="120">
            <col width="95">
            <col>
            <col width="1">
         </colgroup>
         <thead>
            <tr class="headings">
               <th style="display:none">Website</th>
               <th>Cart total</th>
               <th>#Installments</th>
               <th>Currency</th>
               <th class="last">Action</th>
            </tr>
         </thead>
         <tbody id="tier_price_container">';
      $rowHtml = "";   
      foreach ($doctv as $key => $value) {
        $rowHtml .= '<tr>';
        $rowHtml .= '<td> From<br><label>'. $currencySymbolsArray[$value->doctv->currency] .'</label> <input type="text" style="max-width:90%!important;" class="doctv_from" name="doctv_from" value="'.$value->doctv->from.'" /><br>To<br><label>'. $currencySymbolsArray[$value->doctv->currency] .'</label> <input type="text" style="max-width:90%!important;" name="doctv_to" class="doctv_to" value="'.$value->doctv->to.'"/> </td>';
        $rowHtml .= '<td>
                <select id="" name="doctv_installments" class=" select multiselect doctv_installments" size="10" multiple="multiple">';
        $i = 2;
        $installments = explode(",", $value->doctv->installments);
        $selected = "";
        for($i=2; $i<=12; $i++){
          if(in_array($i, $installments)){
            $selected = 'selected="selected"';
          }
          $rowHtml .= '<option value="'.$i.'" '.$selected.'>'.$i.' Installments</option>';  
          $selected = "";
          
        } 
        $rowHtml .= '</select></td>';          
                  
               
        $rowHtml .= '<td>
                <select id="" name="doctv_currency" class=" select doctv_currency">
                  '.$this->_getSelectedCurrency($value->doctv->currency).'
               </td>';
        //$rowHtml .= '<td>'.$this->_getBaseCurrency().'</td>';         
        $rowHtml .= '<td>
                <button title="Delete Tier" type="button" class="scalable delete icon-btn delete-product-option" id="" onclick="deleteRow(this);"><span><span><span>Delete</span></span></span></button>
               </td>
            </tr>';

      }  

      $html .= $rowHtml;
      $html .= '</tbody>
         <tfoot>
         <tr>
               <td style="display:none"></td>
               <td colspan="4" class="a-right"><button id="id_e33ad31ef8ac28bb6e4a4fd4d54f5f9e" title="Add Tier" type="button" class="scalable add" onclick="addRow();" style=""><span><span><span>Add Tier</span></span></span></button></td>
            </tr>
         </tfoot>
      </table>';
            
      return $html;
   }

   // get active currencies in the store and show dropdown in table
   protected function _getCurrencies()
   {
      $currencies = array();
      $codes = Mage::app()->getStore()->getAvailableCurrencyCodes(true);//print_r($codes);die;
      $currenyOptions = "";
      if (is_array($codes) && count($codes) > 1) {
          $rates = Mage::getModel('directory/currency')->getCurrencyRates(
                  Mage::app()->getStore()->getBaseCurrency(),
                  $codes
          );

          foreach ($codes as $code) {
              if (isset($rates[$code])) {
                  $currencies[$code] = Mage::app()->getLocale()
                  ->getTranslation($code, 'nametocurrency');
                  $currenyOptions .='<option value="'.$code.'">'.$code.'</option>';
              }
          }
      }
      return $currenyOptions;
  
   }
  
  // get active currencies and make them selected in dropdown in table
    protected function _getSelectedCurrency($currency)
   {
      $currencies = array();
      $codes = Mage::app()->getStore()->getAvailableCurrencyCodes(true);//print_r($codes);die;
      $currenyOptions = "";
      if (is_array($codes) && count($codes) > 1) {
          $rates = Mage::getModel('directory/currency')->getCurrencyRates(
                  Mage::app()->getStore()->getBaseCurrency(),
                  $codes
          );
          $selected = '';
          foreach ($codes as $code) {
              if (isset($rates[$code])) {
                  $currencies[$code] = Mage::app()->getLocale()
                  ->getTranslation($code, 'nametocurrency');
                  if($code == $currency){
                    $selected = "selected=selected";  
                  }
                  
                  $currenyOptions .='<option value="'.$code.'" '.$selected.'>'.$code.'</option>';
                  $selected = '';
              }
          }
      }
      return $currenyOptions;
  
   }

   protected function _getBaseCurrency(){
    $currentCurrency = Mage::app()->getStore()->getBaseCurrencyCode();
    return $currencyLabel = '<input disabled class="doctv_currency" value="'.$currentCurrency.'"/>';
   }

   protected function _getCurrencySymbol(){
    return Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
   }

   protected function _getAvailableCurrencySymbolsArray(){
      $codes = Mage::app()->getStore()->getAvailableCurrencyCodes(true);
      $currencySymbolsArray = [];
      foreach ($codes as $key => $value) {
        $currencySymbolsArray[$value] = Mage::app()->getLocale()->currency($value)->getSymbol();
      }
      return json_encode($currencySymbolsArray);
   }

   protected function _getFirstAvailableCurrencySymbol(){
      $codes = Mage::app()->getStore()->getAvailableCurrencyCodes(true);
      $firstCurrencySymbol = [];
      foreach ($codes as $key => $value) {
        $firstCurrencySymbol = Mage::app()->getLocale()->currency($value)->getSymbol();
        break;
      }
      return $firstCurrencySymbol;
   }

 
   
}