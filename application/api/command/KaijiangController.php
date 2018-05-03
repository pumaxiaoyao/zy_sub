<?php
/**
 * Created by PhpStorm.
 * User: Connto
 * Date: 2018/1/16
 * Time: 15:37
 */

namespace app\api\command;


use think\console\Command;
use think\console\Input;
use think\console\Output;

class KaijiangController extends Command
{
    protected function configure()
    {
        // 这里的hello就是命令行think后面的参数
        $this->setName('kaijiang')->setDescription('Command kaijiang');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     */
    protected function execute(Input $input, Output $output)
    {
        while (true) {
            echo "\n" . date('Y/m/d H:i:s', time()) . "\n";
            $this->kaijiang();
            sleep(5);
        }
    }

    private function kaijiang()
    {
        $codes = ['jsk3', 'gdklsf', 'bjkl8', 'bjpk10', 'cqssc', 'cakeno'];
        foreach ($codes as $code) {
            if (!in_array($code, ['cqssc', 'bjpk10', 'bjkl8', 'cakeno'])) {
                continue;
            }
            $msg = $this->lottery($code);
            echo $msg;
        }
    }

    private function lottery($code)
    {
        switch ($code) {
            case 'cqssc':
                $className = 'app\cqssc\controller\KaijiangController';
                $methodName = 'lottery';
                break;
            case 'bjpk10':
                $className = 'app\pk10\controller\KaijiangController';
                $methodName = 'lottery';
                break;
            case 'bjkl8':
                $className = 'app\egg\controller\KaijiangController';
                $methodName = 'lottery';
                break;
            case 'cakeno':
                $className = 'app\cake\controller\KaijiangController';
                $methodName = 'lottery';
                break;
            default:
                $className = 'app\lottery\controller\LotteryController';
                $methodName = 'retError';
        }
        $class = new $className();
        $msg = $class->$methodName();
        return $code . '  ' . $msg . "\n\r";
    }

}