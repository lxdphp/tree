<?php
/**
 * 输出类库
 * @since   2017-03-14
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace Home\ORG;


class Response {

    static private $debugInfo = array();
    static private $dataType;
    static private $successMsg = null;

    /**
     * 设置Debug信息
     * @param $info
     */
    static public function debug($info) {
        if (APP_DEBUG) {
            array_push(self::$debugInfo, $info);
        }
    }

    /**
     * 设置data字段数据类型（规避空数组json_encode导致的数据类型混乱）
     * @param string $msg
     */
    static public function setSuccessMsg($msg) {
        self::$successMsg = $msg;
    }

    /**
     * 设置data字段数据类型（规避空数组json_encode导致的数据类型混乱）
     * @param int $type
     */
    static public function setDataType($type = DataType::TYPE_OBJECT) {
        self::$dataType = $type;
    }

    /**
     * 错误输出
     * @param integer $code 错误码，必填！
     * @param string  $msg  错误信息，选填，但是建议必须有！
     * @param array   $data
     */
    static public function error($code, $msg = '', $data = array()) {
        $returnData = array(
            'code' => $code,
            'msg'  => $msg,
            'data' => $data
        );
        if (!empty(self::$debugInfo)) {
            $returnData['debug'] = self::$debugInfo;
        }
        header('Content-Type:application/json; charset=utf-8');
        if (self::$dataType == DataType::TYPE_OBJECT && empty($data)) {
            $returnStr = json_encode($returnData, JSON_FORCE_OBJECT);
        } else {
            $returnStr = json_encode($returnData);
        }
        ApiLog::setResponse($returnStr);
        ApiLog::save();
        exit($returnStr);
    }

    /**
     * 成功返回
     * @param      $data
     * @param null $code
     */
    static public function success($data, $code = null, $extra = array()) {
        $code = is_null($code) ? ReturnCode::SUCCESS : $code;
        $msg = is_null(self::$successMsg) ? '操作成功' : self::$successMsg;
        $returnData = array(
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
            'extra' => $extra
        );
        if (!empty(self::$debugInfo)) {
            $returnData['debug'] = self::$debugInfo;
        }
       
        //print_r($returnStr);exit;
        header('Content-Type:application/json; charset=utf-8');
        $returnStr = json_encode($returnData);
        ApiLog::setResponse($returnStr);
        ApiLog::save();
        //print_r($returnStr);exit;
        $returnStr = self::saves($returnStr);
        //$returnStr = $this->Saves('aaa');
        exit($returnStr);
    }
    /**
     * 测试加密，解密
     * @access public
     * @param 
     * @param array $options  
     * @return json
     */
    public function saves($input){
        //$input = 'v17IKo2O2ZhJy/R/kGxx9A==';
        require_once("Public/php_des_master/Dess.php");
        //require_once("Public/php_des_master/Adapter/DesEncrypt.php");
        //print_r($a);exit;
        $des = new \Dess();
        // $rep=new Crypt3Des('123456');//初始化一个对象，并修改默认密钥
       /* $input="hello world";
        echo "原文：".$input."<br/>";
        $encrypt_card=$des->encrypt($input);
        echo "加密：".$encrypt_card."<br/>";
        echo "解密：".$des->decrypt($des->encrypt($input));
        echo "解密2：".$des->decrypt("cPqCvcZOkQO3mkTTL4qNRg==");*/
        $rest = $des->encrypt($input); 
        return $rest;
    }

}