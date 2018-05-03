<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/22
 * Time: 16:13
 */

namespace app\api\controller\admin;


use app\auth\controller\AdminBaseController;
use GatewayClient\Gateway;
use think\Request;

class GatewayWorkerController extends AdminBaseController
{
    /**
     * 绑定用户和socket id
     *
     * @param Request $request
     *
     * @return array
     */
    public function bindClientId (Request $request)
    {
        $post = $request->only('client_id', 'post');

        $error = $this->validate($post, [
            'client_id' => 'require|alphaNum',
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }

        $client_id = trim($post['client_id']);
        $res = Gateway::bindUid($client_id, $this->user->tokenint);
        if ($res) {
            return $this->jsonData;
        }
        $this->jsonData['status'] = 0;
        $this->jsonData['msg'] = 'bind failed';
        return $this->jsonData;
    }

    /**
     * 解除用户绑定的socket id
     * @return array
     */
    public function unbindClientId ()
    {
        try {
            $client_ids = Gateway::getClientIdByUid($this->user->tokenint);
            foreach ($client_ids as $client_id) {
                Gateway::unbindUid($client_id, $this->user->tokenint);
            }
            $this->jsonData['msg'] = 'unbind success';
            return $this->jsonData;

        } catch (\Exception $exception) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = 'unbind failed';
            return $this->jsonData;
        }
    }
}