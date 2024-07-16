<template>
  <div :class="['home', theme.name, { zoom: menu.state }]">
    <el-container class="container">
      <el-aside width="auto">
        <div class="logo">
          <i v-if="menu.collapse" class="el-icon-cloudy"></i>
          <span v-else>{{ store.sys.name }}</span>
        </div>
        <el-scrollbar>
          <el-menu
            @select="menuSelect"
            :collapse="menu.collapse"
            :default-active="menu.active"
            unique-opened
          >
            <MenuList :menuList="store.menu"></MenuList>
          </el-menu>
        </el-scrollbar>
        <small>©{{ store.base.edition }}</small>
      </el-aside>
      <el-container class="content">
        <el-header height="50px">
          <ul>
            <li @click="switchMenu">
              <el-tooltip content="切换菜单" effect="light">
                <i class="el-icon-c-scale-to-original"></i>
              </el-tooltip>
            </li>
            <li @click="reload">
              <el-tooltip content="重新载入" effect="light">
                <i class="el-icon-refresh-right"></i>
              </el-tooltip>
            </li>
          </ul>
          <ul>
            <li>
              <el-popover trigger="hover">
                <i class="el-icon-collection-tag" slot="reference"></i>
                <el-input
                  :rows="3"
                  v-model="memoData"
                  type="textarea"
                  placeholder="这是个非常小巧实用的本地备忘录"
                ></el-input>
              </el-popover>
            </li>
            <li>
              <el-tooltip content="配色方案" effect="light">
                <i class="el-icon-brush" @click="showTheme"></i>
              </el-tooltip>
            </li>
            <li>
              <el-tooltip content="切换组织" effect="light">
                <i class="el-icon-files" @click="setFrame"></i>
              </el-tooltip>
            </li>
            <li>
              <el-popover trigger="hover" popper-class="userPopover">
                <span slot="reference">
                  {{ store.user.name }}
                  <i class="el-icon-caret-bottom"></i>
                </span>
                <div class="user" @click="switchPage('user')">
                  <el-image :src="store.user.img">
                    <div slot="error">
                      <i class="el-icon-user-solid"></i>
                    </div>
                  </el-image>
                  <div class="info">
                    <span>{{ store.user.user }}</span>
                    <span>{{ store.user.tel }}</span>
                  </div>
                </div>
                <el-divider></el-divider>
                <p @click="switchPage('log')">
                  <i class="el-icon-tickets"></i>
                  操作日志
                </p>
                <p @click="switchPage('backup')">
                  <i class="el-icon-receiving"></i>
                  数据备份
                </p>
                <el-divider></el-divider>
                <p @click="signOut">
                  <i class="el-icon-unlock"></i>
                  退出登录
                </p>
              </el-popover>
            </li>
            <li>
              <el-tooltip content="关于程序" effect="light">
                <template v-if="cloud.data.ver == cloud.data.new">
                  <i class="el-icon-cloudy" @click="showCloud"></i>
                </template>
                <template v-else>
                  <el-badge is-dot>
                    <i class="el-icon-cloudy" @click="showCloud"></i>
                  </el-badge>
                </template>
              </el-tooltip>
            </li>
          </ul>
        </el-header>
        <el-main>
          <el-tabs
            :before-leave="switchLabel"
            @tab-remove="delLabel"
            v-model="label.active"
            type="card"
            class="homeLabel"
          >
            <el-tab-pane
              v-for="(item, index) in label.items"
              :label="item.label"
              :name="item.name"
              :key="item.name"
              closable
            ></el-tab-pane>
          </el-tabs>
          <div class="routerPage">
            <keep-alive :exclude="exclude">
              <router-view v-if="exist" />
            </keep-alive>
          </div>
        </el-main>
        <div class="mask" v-show="menu.state" @click="switchMenu"></div>
      </el-container>
    </el-container>
    <el-dialog
      :visible.sync="frame.dialog"
      title="组织数据"
      width="390px"
      v-madeDialog
    >
      <transition name="el-fade-in">
        <div v-if="frame.dialog">
          <el-form label-width="80px">
            <el-form-item label="组织数据">
              <nodTree
                v-model="frame.frame"
                :treeData="store.frame"
                placeholder="请选择组织数据"
                :checkStrictly="true"
              ></nodTree>
            </el-form-item>
          </el-form>
          <el-alert title="功能介绍" type="success" :closable="false">
            <template solt="description">
              <ul class="frameTip">
                <li>组织列表数据受数据授权影响。</li>
                <li>该功能作用于系统全部功能模块。</li>
                <li>未选择组织数据则默认为全部组织。</li>
              </ul>
            </template>
          </el-alert>
        </div>
      </transition>
      <span slot="footer" class="dialog-footer">
        <el-button @click="frame.dialog = false">取消</el-button>
        <el-button @click="saveFrame" type="primary">保存</el-button>
      </span>
    </el-dialog>
    <el-drawer
      title="配色方案"
      :visible.sync="theme.dialog"
      direction="rtl"
      size="320px"
    >
      <template v-if="theme.dialog">
        <ul class="themeGroup">
          <template v-for="(vo, index) of theme.nums">
            <template v-for="(v, i) of 3">
              <li
                :class="[
                  'T_' + index + '_' + i,
                  { selected: theme.name == 'theme_' + index + '_' + i },
                ]"
                @click="setTheme(index + '_' + i)"
              >
                <div class="lt"></div>
                <div class="tr"></div>
                <div class="lb"></div>
              </li>
            </template>
          </template>
        </ul>
      </template>
    </el-drawer>
    <el-drawer
      title="关于程序"
      :visible.sync="cloud.dialog"
      direction="rtl"
      size="320px"
    >
      <template v-if="cloud.dialog">
        <div class="cloud">
          <p class="ver">当前版本: 社区版</p>

          <div class="group">
            <template v-if="cloud.data.ver == cloud.data.new">
              <el-button type="primary" @click="openHome">官网首页</el-button>
            </template>
            <template v-else>
              <el-button type="success" @click="openUpgrade"
                >在线升级</el-button
              >
            </template>
            <el-button @click="openContact">联系客服</el-button>
          </div>
          <p class="about">版权信息</p>
          <p class="text">
            点可云进销存系统受国家计算机软件著作权（ 2019SR0135099
            ）保护，未经官网正规渠道授权擅自公开产品源文件、以及对产品二次出售或以任何形式二次发布的，我们将保留追究法律责任的权利。
          </p>
          <p class="copyright">
            © 2023
            <a :href="store.base.webSite" target="_blank">NODCLOUD.COM</a>
            版权所有
          </p>
        </div>
      </template>
    </el-drawer>
  </div>
</template>
<script>
import "@/config/theme";
import NodTree from "@/components/lib/NodTree";
import MenuList from "@/components/common/MenuList";
export default {
  name: "Home",
  components: {
    NodTree,
    MenuList,
  },
  data() {
    return {
      exist: true, //组件状态
      exclude: null, //组件排除
      menu: {
        active: "home", //当前菜单标识
        collapse: false, //菜单缩放状态
        state: false, //位移菜单状态
      },
      label: {
        active: "home", //当前标签标识,
        items: [
          {
            name: "home",
            label: "首页",
            resource: "/home",
          },
        ], //标签页数据
      },
      frame: {
        dialog: false,
        frame: [],
      },
      cloud: {
        dialog: false,
        data: {
          ver: "",
          new: "",
          url: "",
        },
      },
      theme: {
        dialog: false,
        nums: 5,
        name: "",
      },
      memoData: "", //备忘录内容
    };
  },
  created() {
    this.$lib.updateStore(this);
    this.$bus.on("homeReload", this.homeReload);
    this.$bus.on("switchPage", this.switchPage);
  },
  computed: {
    //读取数据中心
    store() {
      return this.$store.state;
    },
    //读取页面尺寸
    screen: {
      cache: false,
      get: function () {
        let width = document.body.offsetWidth;
        if (width > 1200) {
          return 3; //大屏幕
        } else if (width > 992) {
          return 2; //中屏幕
        } else if (width > 768) {
          return 1; //小屏幕
        } else {
          return 0; //超小屏幕
        }
      },
    },
    //扁平化菜单数据
    flatMenu() {
      let menu = this.$lib.flatten(this.store.menu);
      menu.push({
        name: "新手向导",
        key: "guide",
        model: 0,
        resource: "/home/guide",
      });
      return menu;
    },
  },
  watch: {
    $route: {
      handler(route) {
        let routeName = route.name == "main" ? "home" : route.name;
        if (routeName != this.menu.active) {
          this.menu.active = routeName;
          if (
            this.label.items.findIndex((item) => item.name == routeName) == -1
          ) {
            this.label.items.push({
              name: route.name,
              label: route.meta.title,
              resource: route.path,
            });
          }
          this.label.active = routeName;
        }
      },
      deep: true,
    },
    //同步备忘录VUEX
    memoData(val) {
      this.$store.commit("upState", {
        key: "memoData",
        val: val,
      });
    },
  },
  methods: {
    //菜单点击事件
    menuSelect(index) {
      let menu = this.flatMenu.find((obj) => obj.key == index); //匹配数据
      if (menu.check == -1) {
        return false;
      } //扩展菜单无效化处理
      //菜单模式
      if (menu.model == 0) {
        //标签模式
        let label = this.label.items.findIndex((obj) => obj.name == menu.key);
        label == -1 &&
          this.label.items.push({
            name: menu.key,
            label:
              menu.name.indexOf("|") == -1
                ? menu.name
                : menu.name.split("|")[1], //兼容附属菜单
            resource: menu.resource,
          });
        this.label.active = menu.key;
        //位移菜单收掉菜单
        this.menu.state && this.switchMenu();
      } else {
        //新页模式
        window.open(menu.resource);
      }
    },
    //标签切换事件
    switchLabel(name) {
      let menu = this.flatMenu.find((obj) => obj.key == name);
      this.menu.active = menu.key;
      if (this.$router.history.current.fullPath != menu.resource) {
        this.$router.push(menu.resource);
      }
    },
    //标签删除事件
    delLabel(name) {
      let index = this.label.items.findIndex((label) => label.name == name);
      if (this.label.active == name) {
        let nextTab =
          this.label.items[index + 1] || this.label.items[index - 1];
        this.menu.active = nextTab.name;
        this.label.active = nextTab.name;
      }
      this.label.items.splice(index, 1);
      //处理组件缓存|keep-alive
      this.exclude = this.$lib.firstToUpperCase(name);
      this.$nextTick(() => {
        this.exclude = null;
      });
    },
    //菜单状态显示隐藏
    switchMenu() {
      if (this.screen >= 2) {
        //正常菜单
        this.menu.collapse = !this.menu.collapse;
      } else {
        //位移菜单
        this.menu.state = !this.menu.state;
      }
    },
    //设置组织数据
    setFrame() {
      this.$axios
        .post("service/getFrame", { by: "nodcloud" })
        .then((result) => {
          if (result.state == "success") {
            this.frame.frame = result.info;
            this.frame.dialog = true;
          } else if (result.state == "error") {
            this.$message({
              type: "warning",
              message: result.info,
            });
          } else {
            this.$message({
              type: "error",
              message: "[ ERROR ] 服务器响应超时!",
            });
          }
        });
    },
    //保存组织数据
    saveFrame() {
      this.$axios
        .post("service/saveFrame", { parm: this.frame.frame })
        .then((result) => {
          if (result.state == "success") {
            this.frame.dialog = false;
            this.$message({
              type: "success",
              message: "设置组织数据成功!",
            });
          } else if (result.state == "error") {
            this.$message({
              type: "warning",
              message: result.info,
            });
          } else {
            this.$message({
              type: "error",
              message: "[ ERROR ] 服务器响应超时!",
            });
          }
        });
    },
    //配色方案
    showTheme() {
      this.theme.dialog = true;
    },
    //设置主题
    setTheme(key) {
      let name = "theme_" + key;
      this.theme.name = name;
      this.$store.commit("upState", {
        key: "theme",
        val: name,
      });
    },
    //版本信息
    showCloud() {
      this.cloud.dialog = true;
    },
    //官网首页
    openHome() {
      window.open(this.store.base.webSite);
    },
    //联系客服
    openContact() {
      window.open(this.store.base.contact);
    },
    //退出登录
    signOut() {
      this.$axios.post("service/out").then((result) => {
        if (result.state == "success") {
          this.$store.commit("upState", { key: "token", val: "" });
          this.$router.push({ name: "Login" });
        } else if (result.state == "error") {
          this.$message({
            type: "warning",
            message: result.info,
          });
        } else {
          this.$message({
            type: "error",
            message: "[ ERROR ] 服务器响应超时!",
          });
        }
      });
    },
    //组件刷新|总线事件
    homeReload(exclude = null) {
      this.exist = false;
      this.exclude = exclude;
      this.$nextTick(() => {
        this.exist = true;
        this.exclude = null;
      });
    },
    //切换页面|总线事件
    switchPage(key, reset = false) {
      let menu = this.flatMenu.find((obj) => obj.key == key); //匹配数据
      if (menu == undefined) {
        console.log("[:( no access]");
      } else {
        //菜单模式
        if (menu.model == 0) {
          //标签模式
          let label = this.label.items.findIndex((obj) => obj.name == menu.key);
          reset && label != -1 && this.delLabel(menu.key);
          (reset || label == -1) &&
            this.label.items.push({
              name: menu.key,
              label:
                menu.name.indexOf("|") == -1
                  ? menu.name
                  : menu.name.split("|")[1], //兼容附属菜单
              resource: menu.resource,
            });
          this.label.active = menu.key;
        } else {
          //新页模式
          window.open(menu.resource);
        }
      }
    },

    //组件刷新
    reload() {
      this.$bus.emit("rootReload");
      this.$message({
        type: "success",
        message: "页面刷新成功!",
      });
    },
  },
  mounted() {
    //兼容页面重载菜单内容
    if (this.$route.name != "main") {
      this.menu.active = this.$route.name;
      this.label.items.push({
        name: this.$route.name,
        label: this.$route.meta.title,
        resource: this.$route.path,
      });
      this.label.active = this.$route.name;
    }
    //恢复备忘录
    this.memoData = this.store.memoData;
    //恢复主题
    this.theme.name = this.store.theme;
    //页面大小改变恢复默认样式
    window.addEventListener("resize", () => {
      this.menu.collapse = false;
      this.menu.state = false;
    });
  },
  beforeDestroy() {
    this.$bus.off("homeReload", this.homeReload);
    this.$bus.off("switchPage", this.switchPage);
  },
};
</script>
<style>
/* 用户下拉样式 */
.userPopover {
  width: 180px;
  min-width: auto;
  padding: 6px;
}
.userPopover .user {
  display: flex;
  cursor: pointer;
}
.userPopover .user .info {
  margin-top: 4px;
  margin-left: 6px;
}
.userPopover .user .info span {
  display: block;
  line-height: 20px;
}
.userPopover .user .el-image {
  width: 50px;
  height: 50px;
}
.userPopover .user .el-image i {
  font-size: 36px;
  padding: 6px;
}
.userPopover p {
  line-height: 32px;
  cursor: pointer;
}
.userPopover p:hover {
  background: #dbedfc;
}
.userPopover p i {
  margin: 0 6px;
}
/* 附属菜单样式 */
.subsidiaryMenu .el-menu-item-group__title {
  display: none;
}
.subsidiaryMenu ul {
  display: flex;
  justify-content: space-between;
}

.subsidiaryMenu ul li:first-child {
  min-width: 70%;
}
.subsidiaryMenu ul li:last-child {
  padding: 0 !important;
  min-width: 30%;
  text-align: center;
}
.subsidiaryMenu ul li:last-child span {
  box-sizing: border-box;
  padding: 2px 4px;
  border: 1px solid #cccccc;
}
</style>
<style scoped>
.container {
  height: 100vh;
}
.content {
  height: 100%;
}
/* 菜单样式 */
.el-aside {
  position: relative;
}
.el-aside .logo {
  height: 50px;
}
.el-aside .logo span {
  display: block;
  font-size: 16px;
  line-height: 50px;
  text-align: center;
  letter-spacing: 1px;
  cursor: pointer;
}

.el-aside .logo i {
  display: block;
  font-size: 18px;
  line-height: 50px;
  text-align: center;
}

.el-aside .el-scrollbar {
  height: calc(100% - 50px);
}

.el-aside .el-menu {
  border-right: none;
  z-index: 2;
}

.el-aside .el-menu:not(.el-menu--collapse) {
  width: 220px;
}

.el-aside
  >>> .el-menu--collapse
  > .menuList
  > .el-submenu
  > .el-submenu__title
  span {
  height: 0;
  width: 0;
  overflow: hidden;
  visibility: hidden;
  display: inline-block;
}

.el-aside >>> .el-submenu__icon-arrow {
  right: 10px;
  margin-top: -5px;
}

.el-aside > small {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 12px;
  text-align: center;
  color: #999;
  letter-spacing: 1px;
  z-index: 1;
  cursor: pointer;
}

.el-header {
  padding: 0;
  font-size: 16px;
  display: flex;
  justify-content: space-between;
}
.el-header li {
  display: inline;
  margin: 0 10px;
  line-height: 50px;
}
.el-header span,
.el-header i {
  cursor: pointer;
  outline: none;
}
.el-header >>> .is-dot {
  top: 16px;
  left: 2px;
}
/* 组织机构 */
.frameTip {
  list-style-type: decimal;
}
.frameTip li {
  line-height: 22px;
}

/* 版本信息 */
.cloud {
  color: #666;
}
.cloud .group {
  text-align: center;
  margin-bottom: 12px;
}
.cloud .about {
  line-height: 32px;
  border-bottom: 1px solid #ccc;
  margin-bottom: 12px;
}
.cloud .text {
  margin-bottom: 12px;
  padding: 14px;
  line-height: 28px;
  background-color: #f2f2f2;
}
.cloud .copyright a {
  color: #409eff;
}

.cloud .ver {
  margin-bottom: 12px;
}
/* 主区域 */
.el-main {
  padding: 0;
}

/* 标签样式 */
.el-main >>> .homeLabel .el-tabs__nav {
  border-top: none !important;
  border-radius: 0 !important;
  border: initial;
  border-right: 1px solid #e4e7ed !important;
}

.el-main >>> .homeLabel .el-tabs__header {
  margin: 0;
}

.el-main >>> .homeLabel .el-tabs__content {
  display: none;
}

.el-main >>> .homeLabel #tab-home span {
  display: none;
}

.el-main >>> .homeLabel .is-active {
  border-bottom: none !important;
}

.el-main >>> .homeLabel .el-tabs__header {
  border: none;
  box-sizing: border-box;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.1);
  z-index: 1;
}

.el-main >>> .homeLabel .el-tabs__nav-wrap {
  margin-bottom: 0;
}

.el-main .routerPage {
  padding: 12px;
  overflow: auto;
  box-sizing: border-box;
  height: calc(100% - 40px);
}

.mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 950513;
  background-color: rgba(0, 0, 0, 0.3);
}

@media screen and (max-width: 992px) {
  .el-aside {
    transform: translateX(-220px);
  }

  .content {
    position: absolute;
    left: 0;
    right: 0;
  }

  .zoom .el-aside {
    transform: translateX(0);
  }

  .zoom .content {
    transform: translateX(220px);
  }
}
</style>
