<?php
/**
 * Sample class comment
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <something@email.com>
 * @license   https://github.com/linhue/wechatPay.git BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
namespace app\api\controller;
use think\Controller;

class WechatPayTest extends Controller
{
    protected $AuthenticationAmount = 1;
    // 该方法引入微信支付sdk
    private function inc_WxApi()
    {
        require_once EXTEND_PATH.'/payment/include.php';
    }
    /**
     * 微信统一下单
     * @return Json
     */
    public function testPay()
    {
        //商户网站唯一订单号
        $order_num = date('Ymdhis');

        //金额 支付宝支持金额低于1 微信交易金额不得小于1
        $amount = $this->AuthenticationAmount;

        //订单号
        $out_trade_no= date('Ymdhis');
        try{
            //引入paymnt扩展文件
            $this->inc_WxApi();
            //获取配置文件
            $config = $this->getWechatPay();
            //实例化扩展类
            $wechatOrder = (new \payment\WePay\Order($config));
            // 组装请求微信统一下单接口的参数
            $options = [
                // 商品描述
                'body'             => '***商品描述***',
                // 商户订单号
                'out_trade_no'     => $out_trade_no,
                // 订单总金额，单位为分
                'total_fee'        => intval($amount),
                // 交易类型
                'trade_type'       => 'APP',
                // 通知回调地址
                'notify_url'       => 'https://api.****.com/app/WechatPayNotify',
                // 终端IP
                'spbill_create_ip' => '127.0.0.1',
            ];
            //向微信发送请求创建订单
            $res = $wechatOrder->create($options);
            if(is_array($res))
            {
                //返回的数据为何重组新的key值 下完面几张图你会理解。
                $data['appid'] = $res['appid'];
                $data['partnerid'] = $res['mch_id'];
                //唯一的订单号
                $data['prepayid'] = $res['prepay_id'];
                $data['timestamp'] = time();
                $data['noncestr'] = $res['nonce_str'];
                //package的值不可更改
                $data['package'] = 'Sign=WXPay';
                //重新加密 数据 返回app端 ，调起支付
                $data['sign'] =(new \WeChat\Contracts\BasicWePay($config))->getPaySign($data);
            }
            $result= ['err_code'=>200,'is_success'=>true,'data'=>data];
        }catch(\Exception $e){
            $result= ['err_code'=>$e->getCode(),'msg'=>$e->getMessage()];
        }
        return json_encode($result);
    }

    /***
     * 回调处理
     * @return string
     */
    public function wechatBackNotify()
    {
        // 以下三行为获取参数
        $xmlData = file_get_contents('php://input');
        libxml_disable_entity_loader(true);
        $result = json_decode(json_encode(simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA)), true);


        //当前为 微信支付回调接口 测试格式数据。
        $result = json_decode('{"appid":"wxd7e785a0db4a696d","bank_type":"CFT","cash_fee":"1","fee_type":"CNY","is_subscribe":"N","mch_id":"1537578331","nonce_str":"ez3bxnte0kxntiqq02h5mi1ip5ri2pra","openid":"***","out_trade_no":"2019112212423824","result_code":"SUCCESS","return_code":"SUCCESS","sign":"D0237A39C0756075D9D67C603C5057AA","time_end":"20191122124247","total_fee":"1","trade_type":"APP","transaction_id":"4200000427201911222799357893"}', true);
        //创建数据库aaa 存放下微信真实发送过来的数据 ，也可以。
        \think\Db::name('aaa')->insert(['json'=>'微信回调通知------------->'.json_encode($result)]);
        //处理回调
        if (!$result) {
            return json_encode(['err_code'=>1001,'msg'=>'非法请求']);
        }

        //成交金额
        $buyer_pay_amount = $result['total_fee'];
        //订单号
        $out_trade_no = $result['out_trade_no'];
        //.............所需的一些订单状态的处理

        //成功失败返回xml格式数据
        if (true) {
            return $this->is_success();
        } else {
            return $this->is_fail();
        }
    }
    protected function is_success()
    {
        return "<xml>
                <return_code><![CDATA[SUCCESS]]></return_code>
                <return_msg><![CDATA[OK]]></return_msg>
                </xml>";
    }
    protected function is_fail()
    {
        return "<xml>
				<return_code><![CDATA[FAIL]]></return_code>
				<return_msg><![CDATA[未找到订单号]]></return_msg>
				</xml>";
    }
    public function getWechatPay()
    {
        return     [
            // 微信开放平台申请用于app支付的appid
            'appid'          => '***appid***',
            // 微信开放平台申请用于app支付的appsecret
            'appsecret'      => '***appsecret***',
            'encodingaeskey' => '***配置商户支付参数***',
            // 配置商户支付参数（可选，在使用支付功能时需要）
            // 微信商户id
            'mch_id'         => "***微信商户id**",
            // 商户api秘钥
            'mch_key'        => '938bfd36a2f5c90f66c59fe51c5e653d',
            // 配置商户支付双向证书目录（可选，在使用退款|打款|红包时需要） ---放置你申请下的证书
            'ssl_key'        => EXTEND_PATH.'/payment/cert/apiclient_key.pem',
            'ssl_cer'        => EXTEND_PATH.'/payment/cert/apiclient_cert.pem',
            // 缓存目录配置（可选，需拥有读写权限）
            'cache_path'     => '',
        ];
    }
}



