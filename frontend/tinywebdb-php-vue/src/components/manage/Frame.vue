<template>
  <div>
    <div class="sticky-top" style="background-color:#fcfcfc;display:block;border-bottom:1px solid #f9f9f9">
      <b-navbar toggleable="lg" class="app-width" style="padding:14px">
        <b-navbar-brand to="/">TinyWebDB后台</b-navbar-brand>
        <b-navbar-toggle target="nav_collapse" />
        <b-collapse is-nav id="nav_collapse">
          <b-navbar-nav>
            <b-nav-item  to="/manage/all">全部标签</b-nav-item>
            <b-nav-item to="/manage/backup">备份/恢复</b-nav-item>
            <b-nav-item to="/manage/file">文件目录</b-nav-item>
            <b-nav-item to="/manage/setting">设置</b-nav-item>
            <b-nav-item v-if="update_available" :href="update_pageUrl" target="_blank">管理系统有更新！</b-nav-item>
          </b-navbar-nav>
          <b-navbar-nav class="ml-auto">
            <b-nav-item to="/manage/logout">登出后台</b-nav-item>
          </b-navbar-nav>
        </b-collapse>
      </b-navbar>
    </div>

    <div class="app-width">
      <div style="margin-top:5px">
        <router-view/>
      </div>

      <div id="footer">
        TinyWebDB MANAGE System By <b-link target="_blank" href="http://github.com/Colintree">Colintree</b-link>.
        VERSION: {{ version }}
        <br>
        <b-link target="_blank" href="http://tsp.colintree.cn/使用手册">需要帮助？</b-link>
        &nbsp;
        <b-link target="_blank" href="http://tsp.colintree.cn/意见反馈">意见反馈</b-link>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ManageFrame',
  props: {
    action: String
  },
  data () {
    return {
      version: '20190322_1',
      update_available: false,
      update_pageUrl: undefined
    }
  }
}
</script>

<style>
.app-width {
  width: 900px;
  margin: 0 auto;
}
#footer {
  margin-top: 20px;
  width: 100%;
  padding-left: 10px;
  font-size: 14px;
}
#footer, #footer a {
  color: #bbb;
}
#footer a {
  text-decoration: underline;
}
</style>