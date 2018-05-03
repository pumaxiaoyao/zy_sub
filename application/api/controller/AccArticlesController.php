<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/31
 * Time: 14:08
 */

namespace app\api\controller;


use app\api\model\AccArticle;
use app\auth\controller\BaseController;

class AccArticlesController extends BaseController
{
    /**
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $params = request()->param();

        $error = $this->validate($params, [
            'page'      => 'number|egt:1',
            'per_page'  => 'number|egt:5',
            'title'     => 'chsDash'
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }

        // 页数
        $page = isset($params['page']) ? $params['page'] : 1;
        if ($page < 1) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '页码不能小于1';
            return $this->jsonData;
        }
        // 每页显示数量
        $per_page = isset($params['per_page']) ? $params['per_page'] : 15;

        // 查询条件
        $where = [];

        if (isset($params['title'])) {
            $where['title'] = ['like', $params['title']];
        }

        $list = AccArticle::getArticles($page, $per_page, $where);

        $url = request()->baseUrl();

        $data = paginate_data($page, $per_page, $params, $where, $list, $url, AccArticle::class, 'getArticles');

        $this->jsonData['data']['articles'] = $data;
        return $this->jsonData;
    }

    /**
     * 显示指定的资源
     *
     * @param  int $id
     * @return array
     * @throws \think\exception\DbException
     */
    public function read($id)
    {
        $article = AccArticle::get($id);
        if (is_null($article)) {
            $this->jsonData['status'] = 404;
            $this->jsonData['msg'] = '文章不存在！';
        } else {
            unset($article->tokenint);
            $this->jsonData['data']['article'] = $article;
        }
        return $this->jsonData;
    }
}