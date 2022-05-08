<?php
 require("wxpusher.class.php");
/**
 * 文章评论推送到微信
 *
 * @package Commentwx
 * @author Evan
 * @version 1.0.0
 * @link https://www.evantu.top
 */
class Commentwx_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Feedback')->comment = array('Commentwx_Plugin', 'fw');
        Typecho_Plugin::factory('Widget_Feedback')->trackback = array('Commentwx_Plugin', 'fw');
        
        return _t('请配置wxpusher信息, 以使您的新评论推送生效');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
    }
 
    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $appToken = new Typecho_Widget_Helper_Form_Element_Text('appToken', null, null, _t('appToken'), _t(' 打开<a href="https://wxpusher.zjiecode.com/admin/login">应用的后台r</a>，从左侧菜单栏，找到appToken菜单获取，具体流程参考<a href="https://wxpusher.zjiecode.com/">WxPusher文档</a>。'));
        $form->addInput($appToken->addRule('required', _t('appToken 不能为空')));

        $uid = new Typecho_Widget_Helper_Form_Element_Text('uid', null, null, _t('uid'), _t('关注公众号：wxpusher，然后点击「我的」-「我的UID」查询到UID。'));
        $form->addInput($uid->addRule('required', _t('uid 不能为空')));

        $summary = new Typecho_Widget_Helper_Form_Element_Text('summary', null, "您的博客收到了新的评论！", _t('summary'), _t('填写消息摘要，显示在微信聊天页面或者模版消息卡片上，限制长度100，可以不传，不传默认截取content前面的内容。'));
        $form->addInput($summary);

        $notMyself = new Typecho_Widget_Helper_Form_Element_Radio('notMyself',
            array(
                '1' => '是',
                '0' => '否'
            ),'1', _t('当评论者为自己时不发送通知'), _t('启用后，若评论者为博主，则不会向微信发送通知，若博主 UID 不为 1，则需要在下方填写博主的 UID'));
        $form->addInput($notMyself);
        
        $customUid = new Typecho_Widget_Helper_Form_Element_Text('customUid', NULL, NULL, _t('自定义博主 UID'), _t('（非必填）自定义博主 UID'));
        $form->addInput($customUid);
    }
    
    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 微信推送
     *
     * @access public
     * @param array $comment 评论结构
     * @param Typecho_Widget $post 被评论的文章
     * @return void
     */
    public static function fw($comment, $post)
    {
        $options = Typecho_Widget::widget('Widget_Options');
        
        $appToken = $options->plugin('Commentwx')->appToken;
        $uid = $options->plugin('Commentwx')->uid;
        $notMyself = $options->plugin('Commentwx')->notMyself;
        $customUid = $options->plugin('Commentwx')->customUid;
        $summary = $options->plugin('Commentwx')->summary;;
        $desp = $comment['author']."：".$comment['text'];
        
        // 判断是否启用当评论者为自己时不发送通知
        if($notMyself == '1') {
            if (!empty($customUid)) {
                if ($comment['authorId'] == $customUid) {
                    return $comment;
                }
            } elseif ($comment['authorId'] == 1) {
                return  $comment;
            }
        }

         $wxpusher = new wxpusher($appToken);
         $wxpusher->send("$desp","$summary",'1','true',"$uid","$post->permalink");
              
        return $comment;
    }
}
