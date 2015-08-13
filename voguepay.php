<?php
/**********************************************
 * AN UNOFFICIAL VOGUEPAY API WRAPPER WRITTEN IN PHP 5 OOP STYLE
 *
 * Name:        VoguePay API Wrapper
 * Version:     1.0
 * Author:      Cobhams, Joseph
 * URL:         http://www.vyrenmedia.com (Vyren Media)
 * License:     GNU GENERAL PUBLIC LICENSE Version 2
 *
 */

class voguePay
{
    //*************** MERCHANT ID *******************
    //  Voguepays' merchant id
    //  Type: String
    private $merchant_id;
    //  Options: open
    //  Default: none

    private $api_host = "https://voguepay.com/api"; //holds api end point for the command APIs
    public $payment_url = "https://voguepay.com/pay/"; //holds the payment link
    private $developer_code; //optional developer code for commissions
    private $api_ver ='1.0'; //voguepay API version
    private $wrapper_ver = '1.0'; //this script version.

    //****************** PROXY SETTINGS *************************/
    //  Configure proxy settings for CURL
    //  Type: string
    private $proxy = "127.0.0.1:8080";
    //  Options: open
    //  Example: address:port
    //  NOTE:- Does not apply to fcg connection type

    //*************** CONNECTION TYPE *******************
    //  The preferred method for server-server connections.
    //  Type: String
    private $connection_type = "curl";
    //  Options: "curl" or "fgc" [fgc = file_get_contents]
    //  Default: curl

    //*************** DEBUG *******************
    //  Debuging flag
    //  Type: Boolean
    private $debug = true;
    public $debug_msg;
    //  Options: true | false
    //  Default: false


    public function __construct($merchant_id,$developer_code='')
    {
        $this->merchant_id = $merchant_id;
        if(!empty($developer_code)) $this->developer_code = $developer_code;
    }

    //PAYMENT FORM METHODS

    public function getPaymentFormParts($part)
    {
        if($part=='header'){ return "<form name=\"voguepay\" action=\"{$this->payment_url}\" method=\"POST\">"; }
        if($part=='footer') { return "</form>"; }
    }

    public function getPaymentFormBody($total,$notify_url,$success_url,$fail_url,$merchant_ref="",$memo="",$items=array(),$store_id="")
    {
        $form_body =
            "
            <input type=\"hidden\" name=\"total\" value=\"{$total}\" />
            <input type=\"hidden\" name=\"v_merchant_id\" value=\"{$this->merchant_id}\" />
            ";
        if(!empty($merchant_ref)) $form_body .= " <input type=\"hidden\" name=\"merchant_ref\" value=\"{$merchant_ref}\" />";
        if(!empty($memo)) $form_body .= " <input type=\"hidden\" name=\"memo\" value=\"{$memo}\" />";
        if(!empty($this->developer_code)) $form_body .= "<input type=\"hidden\" name=\"developer_code\" value=\"{$this->developer_code}\" />";

        if(count($items)>0)
        {
            foreach($items as $item)
            {
                $form_body .= "
            <input type=\"hidden\" name=\"item_{$item['id']}\" value=\"{$item['title']}\" />
            <input type=\"hidden\" name=\"description_{$item['id']}\" value=\"{$item['desc']}\" />
            <input type=\"hidden\" name=\"price_{$item['id']}\" value=\"{$item['price']}\" />
            ";
            }
        }

        $form_body .= "
            <input type=\"hidden\" name=\"notify_url\" value=\"{$notify_url}\" />
            <input type=\"hidden\" name=\"success_url\" value=\"{$success_url}\" />
            <input type=\"hidden\" name=\"fail_url\" value=\"{$fail_url}\" />
            ";
        $form_body .= "<p>Generated by Vyren Media's VoguePay API</p>";
        return $form_body;
    }

    public function getFullPaymentForm($total,$notify_url,$success_url,$fail_url,$merchant_ref="",$memo="",$items=array(),$store_id="")
    {
        $form_body = $this->getPaymentFormParts("header");
        $form_body.= $this->getPaymentFormBody($total,$notify_url,$success_url,$fail_url,$merchant_ref,$memo,$items,$store_id);
        $form_body.= $this->getPaymentFormParts("footer");
        return $form_body;
    }


    //PAYMENT VERIFICATION METHODS

    public function getPaymentDetails($transaction_id,$type="json")
    {
        //currently only json format is supported. But XML would be added soon.
        $url = "https://voguepay.com/?v_transaction_id={$transaction_id}&type={$type}";
        if($this->connection_type =="curl")
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            if($this->proxy)
            {
                curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
                //curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyauth);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            }
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windowos NT 5.1; en-NG; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13 Vyren Media-VoguePay API Ver 1.0");
            if(curl_errno($ch) && $this->debug==true){ $this->debug_msg[] = curl_error($ch)." - [Called In getPaymentDetails() CURL]"; }
            $output = curl_exec($ch);
            curl_close($ch);
        }

        if($this->connection_type =="fgc")
        {
            $output = file_get_contents($url);
            if(!output && $this->debug==true) {$this->debug_msg[] = "Failed To Get JSON Data - [Called In getPaymentDetails() FGC]"; }
        }
        return $output;

    }

    public function verifyPayment($transaction_id)
    {
        $details = json_decode($this->getPaymentDetails($transaction_id,"json"));
        if(!$details && $this->debug==true){ $this->debug_msg[] = "Failed Getting Transaction Details - [Called In verifyPayment()]";}
        if($details->total < 1) return json_encode(array("state"=>"error","msg"=>"Invalid Transaction"));
        if($details->status != 'Approved') return json_encode(array("state"=>"error","msg"=>"Transaction {$details->status}"));
        return json_encode(array("state"=>"success","msg"=>"Transaction Approved"));
    }

}
?>