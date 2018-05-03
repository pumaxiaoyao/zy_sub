<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/4/7
 * Time: 17:42
 */

namespace app\index\controller;


use think\Controller;
use app\cake\model\CakeLottery;
use app\cqssc\model\SscLottery;
use app\egg\model\EggLottery;
use app\pk10\model\Pk10Lottery;
use think\Hook;

class KaijiangController extends Controller
{

    public function index ()
    {
        return $this->fetch();
    }


    /**
     * 行情收集
     * @throws \think\exception\DbException
     */
    public function collectLty ()
    {
        $codes = implode(',', ['jsk3', 'gdklsf', 'bjkl8', 'bjpk10', 'cqssc', 'cakeno']);
        $ip = 'http://120.78.203.162:8081/';
        $num = 5;
        $dataType = 'json';
        $tokenid = '33333333333333333333333333333333';
        $url = $ip . $codes . "/" . $num . "/" . $dataType . "/" . $tokenid;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        if (false === $res) {
            $this->collectLtyBak();
        }
        if (strpos($res, '访问间隔应大于')) {
            sleep(5);
            return json([]);
        }
        $result = $this->collectJsonData2($res);
        return json($result);
    }

    /**
     * 处理获取到的行情
     *
     * @param $jsonStr
     *
     * @return array|string
     * @throws \think\exception\DbException
     */
    private function collectJsonData2 ($jsonStr)
    {
        // 组装数据
        $msgs = [];
        $lotteryList = json_decode($jsonStr, true);
        if (!is_array($lotteryList)) {
            return [];
        }
        foreach ($lotteryList['data'] as $lottery) {
            $code = $lottery['code'];
            if (!in_array($code, ['cqssc', 'bjpk10', 'bjkl8', 'cakeno'])) {
                continue;
            }
            // 判断数据是否已经写入表中，写一个方法
            // if ($this->exist($lottery['expect'], $code)) {
                // continue;
            // }
            // 判断下来的数据是否大于已经下来的数据的最新期号
            // 如果大于则写入，否则跳过
            $lastExpect = $this->lastExpect($code);
            if ($lastExpect >= $lottery['expect']) {
                continue;
            }
            $msg = $this->saveData2($lottery, $code);
            array_push($msgs, $msg);
        }
        return $msgs;
    }

    /**
     * @return array
     * @throws \think\exception\DbException
     */
    public function collectLtyBak()
    {
        $codes = ['jsk3', 'gdklsf', 'bjkl8', 'bjpk10', 'cqssc', 'cakeno'];

        $msgs = [];
        foreach ($codes as $code) {
            if (!in_array($code, ['cqssc', 'bjpk10', 'bjkl8', 'cakeno'])) {
                continue;
            }
            $url = 'http://120.78.62.69:8081/' . $code;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $res = curl_exec($ch);
            $msg = $this->collectJsonData($res);
            if ($msg) {
                array_push($msgs, $msg);
            }
        }
        return json($msgs);
    }

    /**
     * @param $jsonStr
     *
     * @return string
     * @throws \think\exception\DbException
     */
    private function collectJsonData($jsonStr)
    {
        // 组装数据
        $lotteryList = json_decode($jsonStr, true);
        $code = $lotteryList['code'];
        $insertData = [];
        if (!is_array($lotteryList['data'])) {
            return 'collect fail';
        }
        foreach ($lotteryList['data'] as $lottery) {
            // 判断数据是否已经写入表中，写一个方法
            // if ($this->exist($lottery['expect'], $code)) {
                // continue;
            // }
            // 判断下来的数据是否大于已经下来的数据的最新期号
            // 如果大于则写入，否则跳过
            $lastExpect = $this->lastExpect($code);
            if ($lastExpect >= $lottery['expect']) {
                continue;
            }
            $lottery['code'] = $code;
            array_push($insertData, $lottery);
        }
        // 写入数据库
        sort($insertData);
        $res = $this->saveData($insertData, $code);

        return $res;
    }

    /**
     * 检查是否已经有数据
     *
     * @param $expect
     * @param $code
     *
     * @return bool
     * @throws \think\exception\DbException
     */
    private function exist ($expect, $code)
    {
        $res = false;
        switch ($code) {
            case 'cqssc':
                $res = SscLottery::expectExists($expect);
                break;
            case 'bjpk10':
                $res = Pk10Lottery::expectExists($expect);
                break;
            case 'bjkl8':
                $res = EggLottery::expectExists($expect);
                break;
            case 'cakeno':
                $res = CakeLottery::expectExists($expect);
                break;
        }
        return $res;
    }

    /**
     * 查询最新一期开奖期号
     *
     * @param [type] $code
     * @return void
     */
    private function lastExpect($code)
    {
        switch($code) {
            case 'cqssc':
                $res = SscLottery::getLastest();
                break;
            case 'bjpk10':
                $res = Pk10Lottery::getLastest();
                break;
            case 'bjkl8':
                $res = EggLottery::getLastest();
                break;
            case 'cakeno':
                $res = CakeLottery::getLastest();
                break;
            default:
                return 0;
        }
        return $res->expect;
    }

    /**
     * 行情入库
     *
     * @param $insertData
     * @param $code
     *
     * @return bool|string
     * @throws \think\exception\DbException
     */
    private function saveData2 ($insertData, $code)
    {
        switch ($code) {
            case 'cqssc':
                $lotteryModel = new SscLottery();
                break;
            case 'bjpk10':
                $lotteryModel = new Pk10Lottery();
                break;
            case 'bjkl8':
                $lotteryModel = new EggLottery();
                break;
            case 'cakeno':
                $lotteryModel = new CakeLottery();
                break;
            default:
                $lotteryModel = null;
                break;
        }
        $res = $lotteryModel->insert($insertData);
        if ($res) {
            if ('cqssc' == $code) {
                $result = $this->sscKj();
            }
            if ('bjpk10' == $code) {
                $result = $this->pk10Kj();
            }
            if ('bjkl8' == $code) {
                $result = $this->eggKj();
            }
            if ('cakeno' == $code) {
                $result = $this->cakeKj();
            }
            $result['msg0'] = $code . "[ {$insertData['expect']} ]" . " 行情采集成功<br>";
            $result['type'] = $code;
            $result['time'] = date('Y-m-d H:i:s') . "<br>";
            return $result;
        }
        return false;
    }

    /**
     * 行情入库
     *
     * @param $insertData
     * @param $code
     *
     * @return bool|string
     * @throws \think\exception\DbException
     */
    private function saveData ($insertData, $code)
    {
        switch ($code) {
            case 'cqssc':
                $lotteryModel = new SscLottery();
                break;
            case 'bjpk10':
                $lotteryModel = new Pk10Lottery();
                break;
            case 'bjkl8':
                $lotteryModel = new EggLottery();
                break;
            case 'cakeno':
                $lotteryModel = new CakeLottery();
                break;
            default:
                $lotteryModel = null;
                break;
        }
        $res = $lotteryModel->insertAll($insertData);
        if ($res) {
            if ('cqssc' == $code) {
                $result = $this->sscKj();
            }
            if ('bjpk10' == $code) {
                $result = $this->pk10Kj();
            }
            if ('bjkl8' == $code) {
                $result = $this->eggKj();
            }
            if ('cakeno' == $code) {
                $result = $this->cakeKj();
            }
            $result['msg0'] = $code . "[ {$insertData[0]['expect']} ]" . " 行情采集成功<br>";
            $result['type'] = $code;
            $result['time'] = date('Y-m-d H:i:s') . "<br>";
            return $result;
        }
        return false;
    }

    /**
     * 重庆时时彩开奖
     * @throws \think\exception\DbException
     */
    private function sscKj ()
    {
        $sscKj = new \app\cqssc\controller\KaijiangController();
        $result = $sscKj->manualLottery();
        return $result;
    }

    /**
     * 北京PK10开奖
     * @throws \think\exception\DbException
     */
    private function pk10Kj ()
    {
        $pk10Kj = new \app\pk10\controller\KaijiangController();
        $result = $pk10Kj->manualLottery();
        return $result;
    }

    /**
     * PC蛋蛋开奖
     * @throws \think\exception\DbException
     */
    private function eggKj ()
    {
        $eggKj = new \app\egg\controller\KaijiangController();
        $result = $eggKj->manualLottery();
        return $result;
    }

    /**
     * 加拿大28开奖
     * @throws \think\exception\DbException
     */
    private function cakeKj ()
    {
        $cakeKj = new \app\cake\controller\KaijiangController();
        $result = $cakeKj->manualLottery();
        return $result;
    }
}