<?php
/**
 * Created by PhpStorm.
 * User: Hanson
 * Date: 2016/12/13
 * Time: 20:56
 */

namespace Hanson\Vbot\Collections;


use Illuminate\Support\Collection;

class Contact extends Collection
{
    const ADD = 2;

    /**
     * @var Contact
     */
    static $instance = null;

    /**
     * create a single instance
     *
     * @return Contact
     */
    public static function getInstance()
    {
        if(static::$instance === null){
            static::$instance = new Contact();
        }

        return static::$instance;
    }

    /**
     * 根据微信号获取联系人
     *
     * @param $id
     * @return mixed
     */
    public function getContactById($id)
    {
        return $this->filter(function($item, $key) use ($id){
            if($item['Alias'] === $id){
                return true;
            }
        })->first();
    }

    /**
     * 根据微信号获取联系username
     *
     * @param $id
     * @return mixed
     */
    public function getUsernameById($id)
    {
        return $this->search(function($item, $key) use ($id){
            if($item['Alias'] === $id){
                return true;
            }
        });
    }
    /**
     * 根据通讯录中的备注获取通讯对象
     *
     * @param $id
     * @return mixed
     */
    public function getUsernameByRemarkName( $id)
    {
        return $this->search(function($item, $key) use ($id){
            if($item['RemarkName'] === $id){
                return true;
            }
        });
    }

    /**
     * 根据通讯录中的昵称获取通讯对象
     *
     * @param $nickname
     * @return mixed
     */
    public function getUsernameByNickname($nickname)
    {
        return $this->search(function($item, $key) use ($nickname){
            if($item['NickName'] === $nickname){
                return true;
            }
        });
    }

    /**
     * 设置备注名
     *
     * @param $username
     * @param $remarkName
     * @return bool
     */
    public function setRemarkName($username, $remarkName)
    {
        $url = sprintf('%s/webwxoplog?lang=zh_CN&pass_ticket=%s', server()->baseUri, server()->passTicket);

        $result = http()->post($url, json_encode([
            'UserName' => $username,
            'CmdId' => 2,
            'RemarkName' => $remarkName,
            'BaseRequest' => server()->baseRequest
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), true);

        return $result['BaseResponse']['Ret'] == 0;
    }

    /**
     * 设置/取消置顶
     *
     * @param $username
     * @param bool $isStick
     * @return bool
     */
    public function setStick($username, $isStick = true)
    {
        $url = sprintf('%s/webwxoplog?lang=zh_CN&pass_ticket=%s', server()->baseUri, server()->passTicket);

        $result = http()->json($url, [
            'UserName' => $username,
            'CmdId' => 3,
            'OP' => (int) $isStick,
            'BaseRequest' => server()->baseRequest
        ], true);

        return $result['BaseResponse']['Ret'] == 0;
    }


    public function requestContact($username)
    {
        $this->verifyUser(static::ADD);
    }

    /**
     * 验证通过好友或者主动添加好友
     *
     * @param $code
     * @param null $ticket
     * @param string $content 添加好友附加消息
     * @return bool
     */
    public function verifyUser($code, $ticket = null, $content = '')
    {
        $url = sprintf(server()->baseUri . '/webwxverifyuser?lang=zh_CN&r=%s&pass_ticket=%s', time() * 1000, server()->passTicket);
        $data = [
            'BaseRequest' => server()->baseRequest,
            'Opcode' => $code,
            'VerifyUserListSize' => 1,
            'VerifyUserList' => [$ticket ?: $this->verifyTicket()],
            'VerifyContent' => $content,
            'SceneListCount' => 1,
            'SceneList' => [33],
            'skey' => server()->skey
        ];

        $result = http()->json($url, $data, true);

        return $result['BaseResponse']['Ret'] == 0;
    }

    /**
     * 返回通过好友申请所需的数组
     *
     * @return array
     */
    private function verifyTicket()
    {
        return [
            'Value' => $this->info['UserName'],
            'VerifyUserTicket' => $this->info['Ticket']
        ];
    }

    private function addUserTicket($username)
    {
        return [
            'Value' => $username,
            'VerifyUserTicket' => ''
        ];
    }

}