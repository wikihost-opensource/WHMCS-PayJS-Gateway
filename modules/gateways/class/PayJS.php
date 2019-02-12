<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}


use Illuminate\Database\Capsule\Manager as Capsule;

class PayJS
{
    public function getConfiguration(){
        global $CONFIG;
        $base_config = [
            "FriendlyName" => ['Type' => 'System','Value' => 'WikiHost - PayJS模块',],
        ];        
        $extra_config = [
            "member_key" => ["FriendlyName" => "商户号", "Type" => "text", "Size" => "50" ],
            "access_key" => ["FriendlyName" => "通讯密钥", "Type" => "text", "Size" => "50" ],
            "notice" => [
                'FriendlyName' => '',
                'Type' => 'dropdown',
                'Options' => [
                    '1' => "</option></select><div class='alert alert-info' role='alert' id='payjs_notice' style='margin-bottom: 0px;'>以上信息均可以在 <a href='https://payjs.cn/dashboard/member' target='_blank'><span class='glyphicon glyphicon-new-window'></span> PayJS 管理首页</a> 找到 。</div><script>$('#payjs_notice').prev().hide();</script><select style='display:none'>",
                    ],
            ]
        ];
        
        $config = array_merge($base_config,$extra_config);
        $config["author"] = [
            'FriendlyName' => '',
            'Type' => 'dropdown',
            'Options' => [
                '1' => "</option></select><div class='alert alert-success' role='alert' id='payjs_author' style='margin-bottom: 0px;'>该插件由 <a href='https://idc.wiki' target='_blank'><span class='glyphicon glyphicon-new-window'></span> 微基主机服务</a> 开发 ， 本款插件为免费开源插件<br/><span class='glyphicon glyphicon-ok'></span> 支持 WHMCS 6/7 , 当前WHMCS 版本 ".$CONFIG["Version"]."<br/><span class='glyphicon glyphicon-ok'></span> 仅支持 PHP 5.4 以上的环境 , 当前PHP版本 ".phpversion()."</div><script>$('#payjs_author').prev().hide();</script><style>* {font-family: Microsoft YaHei Light , Microsoft YaHei}</style><select style='display:none'>",
            ],
        ];
        return $config;
    }

    private function call($data, $url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $rst = curl_exec($ch);
        curl_close($ch);
        return json_decode($rst, true);
    }

    private function pay($attr){
        return $this->call($attr, 'https://payjs.cn/api/native');
    }    

    public function getHtml($params){
        $attr = [
            'out_trade_no' => $params['invoiceid'],
            'total_fee' => $params['amount'] * 100,
            "body" => $params["description"],
            'mchid' => $params['member_key'],
            'notify_url' => $params['systemurl'] . '/modules/gateways/callback/payjs.php'
        ];

        $attr['sign'] = $this->sign($attr, $params['access_key']);
        $result = $this->pay($attr);
        if ($result['return_msg'] == 'SUCCESS'){
            $template = file_get_contents(__DIR__ . '/payjs/skin/qr_code.tpl');
            $template = str_replace('{$url}', $result['qrcode'], $template);
            return $template;
        }
        return '<span style="color:red">无法创建支付订单, 请稍后再试</span>';
    }

    public function checkCallback(array $post, array $gateway_data){
        if (!$this->checkSign($post, $gateway_data['access_key'])){
            return json_encode(['error' => true, 'message' => 'sign error']);
        }

        $invoice_id = $post['out_trade_no'];
        $amount = $post['total_fee'] / 100;
        $trade_no = $post['payjs_order_id'];
        $fee = 0;

        if ($post['return_code']){
            $invoiceid = checkCbInvoiceID($invoice_id, $gateway_data["name"]);
            $amount = $this->convert_helper($invoice_id, $amount);
            checkCbTransID($trade_no);
            addInvoicePayment($invoice_id, $trade_no, $amount, $fee, 'payjs');
            return json_encode(['error' => false, 'message' => 'success']);
        }
        return json_encode(['error' => true, 'message' => 'unknown data']);
    }

    private function convert_helper($invoiceid,$amount){
        $setting = Capsule::table("tblpaymentgateways")->where("gateway","payjs")->where("setting","convertto")->first();
        ///系统没多货币 , 直接返回
        if (empty($setting)){ return $amount; }
        
        
        ///获取用户ID 和 用户使用的货币ID
        $data = Capsule::table("tblinvoices")->where("id", $invoiceid)->get()[0];
        $userid = $data->userid;
        $currency = getCurrency( $userid );
    
        /// 返回转换后的
        return  convertCurrency( $amount, $setting->value, $currency["id"] );
    }

    private function checkSign(array $attr, $key){
        $old_sign = $attr['sign'];
        unset($attr['sign']);
        $new_sign = $this->sign($attr, $key);
        return ($old_sign == $new_sign);
    }

    private function sign(array $attr, $key) {
        ksort($attr);
        $sign = strtoupper(md5(urldecode(http_build_query($attr)) . '&key=' . $key));
        return $sign;
    }
}