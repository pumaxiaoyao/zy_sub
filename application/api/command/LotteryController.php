<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/6
 * Time: 15:28
 */

namespace app\api\command;


use app\cake\model\CakeLottery;
use app\cqssc\model\SscLottery;
use app\egg\model\EggLottery;
use app\pk10\model\Pk10Lottery;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Hook;

class LotteryController extends Command
{
    /**
     *
     */
    protected function configure()
    {
        $this->setName('collectLty')->setDescription('Command collect lottery data');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     * @throws \think\exception\DbException
     */
    protected function execute(Input $input, Output $output)
    {
        while (true) {
            echo "\n" . date('Y/m/d H:i:s', time()) . "\n";
            $this->collectData();
            sleep(3);
        }
    }

    /**
     * @return string
     * @throws \think\exception\DbException
     */
    /*private function collectData()
    {
        $codes = ['jsk3', 'gdklsf', 'bjkl8', 'bjpk10', 'cqssc', 'cakeno'];

        foreach ($codes as $code) {
            if (!in_array($code, ['cqssc', 'bjpk10', 'bjkl8', 'cakeno'])) {
                continue;
            }
            $url = 'http://120.78.62.69:8081/' . $code;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $res = curl_exec($ch);
            $msg = $this->collectJsonData($res);
            echo $code . "\t" . $msg . "\n\r";
        }
    }*/

    private function collectData()
    {
        $codes = implode(',', ['jsk3', 'gdklsf', 'bjkl8', 'bjpk10', 'cqssc', 'cakeno']);
        $ip = 'http://120.78.203.162:8081/';
        $num = 1;
        $dataType = 'json';
        $tokenid = '11111111111111111111111111111111';
        $url = $ip . $codes . "/" . $num . "/" . $dataType . "/" . $tokenid;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        $result = $this->collectJsonData2($res);
        return $result;
    }

    /**
     * 处理获取到的行情
     * @param $jsonStr
     *
     * @return array|string
     * @throws \think\exception\DbException
     */
    private function collectJsonData2($jsonStr)
    {
        // 组装数据
        $msgs = [];
        $lotteryList = json_decode($jsonStr, true);
        if (!is_array($lotteryList['data'])) {
            return 'collect fail';
        }
        foreach ($lotteryList['data'] as $lottery) {
            $code = $lottery['code'];
            if (!in_array($code, ['cqssc', 'bjpk10', 'bjkl8', 'cakeno'])) {
                continue;
            }
            // 判断数据是否已经写入表中，写一个方法
            if ($this->exist($lottery['expect'], $code)) {
                continue;
            }
            $msg = $this->saveData($lottery, $code);
            $msg['type'] = $code;
            array_push($msgs, $msg);
        }
        return $msgs;
    }

    /**
     * @param $jsonStr
     * @return string
     * @throws \think\exception\DbException
     */
    /*private function collectJsonData($jsonStr)
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
            if ($this->exist($lottery['expect'], $code)) {
                continue;
            }
            $lottery['code'] = $code;
            array_push($insertData, $lottery);
        }
        // 写入数据库
        sort($insertData);
        $res = $this->saveData($insertData, $code);

        if ($res) {
            return 'save successful';
        } else {
            return 'already exist';
        }
    }*/

    /**
     * 检查是否已经有数据
     * @param $expect
     * @param $code
     * @return bool
     * @throws \think\exception\DbException
     */
    private function exist($expect, $code)
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
     * @param $insertData
     * @param $code
     *
     * @return bool
     */
    private function saveData($insertData, $code)
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

        $res = $lotteryModel->isUpdate(false)->save($insertData);
        if ($res) {
            if ('cqssc' == $code) {
                Hook::listen('kj_ssc');
            }
            if ('bjpk10' == $code) {
                Hook::listen('kj_pk10');
            }
            if ('bjkl8' == $code) {
                Hook::listen('kj_egg');
            }
            if ('cakeno' == $code) {
                Hook::listen('kj_cake');
            }
            return true;
        }
        return false;
    }
}