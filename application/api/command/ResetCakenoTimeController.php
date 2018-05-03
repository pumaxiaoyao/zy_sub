<?php
/**
 * Created by PhpStorm.
 * User: Connto
 * Date: 2018/1/23
 * Time: 16:45
 */

namespace app\api\command;


use app\cake\model\Base;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class ResetCakenoTimeController extends Command
{
    protected function configure()
    {
        // 这里的hello就是命令行think后面的参数
        $this->setName('resetCakeno')->setDescription('Command to make cakeno opentime correct!');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    protected function execute(Input $input, Output $output)
    {
        while (true) {
            echo "\n" . date('Y/m/d H:i:s', time()) . "\n";
            // 0. 获取命令执行时的一条行情
            $data = $this->fetchCakeno();

            // 1. 计算并重写加拿大28的开盘时间管理表
            $timesArr = $this->resetTime($data);

            // 2. 清空原管理表数据，写入新的数据
            $res = $this->insertTime($timesArr);
            if ($res) {
                $output->writeln("\n Reset Successful \n");
            } else {
                $output->writeln("\n Reset Failure \n");
            }
            sleep(3600);
        }

    }

    /**
     * @param $arr
     *
     * @return bool
     */
    private function insertTime($arr)
    {
        Base::name('cake_timelist')->delete(true);
        $res = Base::name('cake_timelist')->insertAll($arr);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function resetTime($data)
    {
        $expect = $data['expect'];
        $opentimestamp = $data['opentimestamp'];
        $opentimestamp2 = strtotime(date('Y-m-d H:i', $opentimestamp));
        $offset = $opentimestamp - $opentimestamp2;
        if ($offset < 30) {
            $draw = strtotime(date('Y-m-d H:i:00', $opentimestamp));
        } else {
            $draw = strtotime(date('Y-m-d H:i:30', $opentimestamp));
        }
        $arr = [];
        for ($i = 0; $i < 377; $i++) {
            $arr[$i]['id'] = $i + 1;
            $arr[$i]['qishu'] = $i;
            $arr[$i]['expect'] = $expect + $i;
            $arr[$i]['draw'] = date('Y-m-d H:i:s', $draw);
            $arr[$i]['close_bet'] = date('Y-m-d H:i:s', $draw - 60);
            $arr[$i]['open_bet'] = date('Y-m-d H:i:s', $draw - 3.5 * 60);
            $arr[$i]['ok'] = 0;
            if ($draw >= strtotime(date('20:00:00')) && $draw <= strtotime(date('22:00:00'))) {
                break;
            }
            $draw += 3.5 * 60;
        }
        return $arr;
    }

    /**
     * @return mixed
     */
    private function fetchCakeno()
    {
        $tokenid = '11111111111111111111111111111111';
        $url = 'http://120.78.203.162:8081/cakeno/5/json/' . $tokenid;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        $arr = json_decode($res, true);
        $data = $arr['data'];
        return $data[0];
    }
}