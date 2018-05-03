<?php

namespace app\api\controller\admin;

use app\api\model\AccArticle;
use app\auth\controller\AdminBaseController;
use think\Request;
use think\Response;

class AccArticlesController extends AdminBaseController
{
    /**
     * 显示资源列表
     *
     * @return array
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
     * 显示创建资源表单页.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * 添加充值文章
     * @param Request $request
     * @return mixed
     */
    public function save(Request $request)
    {
        $post = $request->only(['title', 'content', 'type'], 'post');

        $error = $this->validate($post, [
            'title'     => 'require|chsDash',
            'content'   => 'require',
            'type'      => 'require|between:0,3'
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }

        $article = new AccArticle();
        $article->username = $this->user->username;
        $article->nickname = $this->user->nickname;
        $article->tokenint = $this->user->tokenint;
        $article->title = $post['title'];
        $article->author = $this->user->username;
        $article->content = $post['content'];
        $article->type = $post['type'];
        $article->create_time = time();
        $article->update_time = time();
        $res = $article->save();
        if ($res) {
            $this->jsonData['msg'] = '添加成功';
        } else {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '添加失败';
        }
        return $this->jsonData;
    }

    /**
     * 查看指定文章
     * @param $id
     * @return mixed
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

    /**
     * 显示编辑资源表单页.
     *
     * @param  int $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 修改充值文章
     * @param Request $request
     * @param $id
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function update(Request $request, $id)
    {
        if (!AccArticle::isExists($id)) {
            $this->jsonData['status'] = 404;
            $this->jsonData['msg'] = '文章不存在';
            return $this->jsonData;
        }
        $put = $request->only(['title', 'content', 'type'], 'put');

        $error = $this->validate($put, [
            'title'     => 'require|chsDash',
            'content'   => 'require',
            'type'      => 'require|between:0,3'
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }

        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }
        $upd = $put;
        $upd['id'] = $id;
        $upd['update_time'] = time();
        $article = new AccArticle();
        $result = $article->isUpdate()->save($upd);
        if ($result) {
            $this->jsonData['msg'] = '修改成功';
        } else {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '修改失败';
        }
        return $this->jsonData;
    }

    /**
     * 删除指定充值文章
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $result = AccArticle::destroy($id);
        if ($result) {
            $this->jsonData['msg'] = '删除成功';
        } else {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '删除失败';
        }
        return $this->jsonData;
    }

    /**
     * 文章上传图片，限制大小0.5M
     * @param Request $request
     * @return mixed
     */
    public function upload(Request $request)
    {
        $files = $request->file('image');
        $data = [];
        foreach ($files as $file) {
            $path = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'article';
            $info = $file->validate(['size' => 1024 * 1024 * 0.5, 'ext' => 'jpg,png,jpeg,gif,bmp'])->move($path);
            if ($info) {
                $fileName = $info->getSaveName();
                $url = 'uploads' . DS . 'article' . DS . $fileName;
                $filePath = url($url, '', true, true);
                array_push($data, $filePath);
            } else {
                $error = $file->getError();
                $ret['errno'] = 1;
                $ret['data'] = $error;
                return $ret;
            }
        }
        $ret['errno'] = 0;
        $ret['data'] = $data;
        return $ret;
    }
}
