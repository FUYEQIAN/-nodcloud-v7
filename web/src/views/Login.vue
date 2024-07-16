<template>
  <div class="login">
    <div class="box">
      <div class="chunk">
        <span>Welcome Login</span>
        <p>{{ store.sys.company }}</p>
      </div>
      <div class="form">
        <div class="title">
          <span>{{ store.sys.name }}</span>
          <small>化繁为简 · 为高效运作助力</small>
        </div>
        <el-form :model="form" :rules="rules" ref="form">
          <el-form-item prop="user">
            <el-input
              size="medium"
              type="text"
              v-model="form.user"
              prefix-icon="el-icon-user"
              placeholder="请输入用户账号|手机号"
              autofocus
              clearable
            ></el-input>
          </el-form-item>
          <el-form-item prop="pwd">
            <el-input
              size="medium"
              type="password"
              v-model="form.pwd"
              prefix-icon="el-icon-lock"
              placeholder="请输入用户密码"
              show-password
              clearable
            ></el-input>
          </el-form-item>

          <el-button size="medium" type="primary" class="btn" @click="login"
            >安全登录</el-button
          >
        </el-form>
      </div>
    </div>
    <div class="footer">
      ERP {{ store.base.ver }} | Copyright ©
      <a :href="store.base.webSite" target="_blank">{{
        store.base.copyright
      }}</a>
      <a href="https://beian.miit.gov.cn/" target="_blank">{{
        store.sys.icp
      }}</a>
     
      <a  style="height: 24px; margin-left: 10px;"  href='https://gitee.com/diankeyun/erp-community' target="_blank"><img src='https://gitee.com/diankeyun/erp-community/badge/star.svg?theme=dark' alt='star'></img></a>
      
    </div>
  </div>
</template>
<script>
export default {
  name: "Login",
  data() {
    return {
      form: {
        user: "",
        pwd: "",
        uuid: "",
      },
      rules: {
        user: {
          required: true,
          message: "请输入用户账号|手机号",
          trigger: "blur",
        },
        pwd: {
          required: true,
          message: "请输入用户密码",
          trigger: "blur",
        },
      },
    };
  },
  created() {
    //获取运行数据
    this.getCaptcha();
    this.getRunData();
  },
  computed: {
    store() {
      return this.$store.state;
    },
  },
  methods: {
    getCaptcha() {
      this.$axios.post("api/captcha", this.form).then((result) => {
        if (result.state == "success") {
          this.form.code = "";
          this.form.uuid = result.info.uuid;
          this.$refs.captcha.src = result.info.data;
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
    //登录
    login() {
      this.$refs["form"].validate((valid) => {
        if (valid) {
          this.$axios.post("api/login", this.form).then((result) => {
            if (result.state == "success") {
              this.$store.commit("upState", {
                key: "user",
                val: result.info,
              });
              this.$store.commit("upState", {
                key: "token",
                val: result.token,
              });
              this.$router.push("/home");
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
        }
      });
    },
    //获取运行数据
    getRunData() {
      this.$axios.post("api/runData").then((result) => {
        if (result.state == "success") {
          this.$store.commit("upState", {
            key: "sys",
            val: result.info.sys,
          });
          this.$store.commit("upState", {
            key: "base.ver",
            val: result.info.ver,
          });
          if (result.info.login == true) {
            this.$router.push("/home");
          }
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
  },
};
</script>
<style scoped>
.login {
  position: absolute;
  background: #2d3135;
  width: 100vw;
  height: 100vh;
  background: url(/static/images/login/bg.png);
  background-repeat: no-repeat;
  background-size: cover;
}
.box {
  display: flex;
  width: 860px;
  height: 460px;
  margin: 0 auto;
  margin-top: 16vh;
  position: relative;
  z-index: 2;
  box-shadow: 0 0 16px 8px #1261c1;
}

.form {
  flex: 1;
  background: #fff;
  padding: 62px 0;
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
}
.title > span {
  font-size: 18px;
  display: block;
  text-align: center;
  color: #5c5f68;
  line-height: 36px;
  font-weight: bold;
  letter-spacing: 2px;
}
.title > small {
  display: block;
  text-align: center;
  color: #d1d1d1;
  line-height: 24px;
  letter-spacing: 1px;
}
.form .el-form {
  width: 320px;
  margin: 0 auto;
  margin-top: 24px;
}

.el-form-item {
  margin-bottom: 24px;
}
.el-form >>> .el-form-item__error {
  padding: 4px 0;
}
.captchaGroup {
  display: flex;
}
.captchaGroup .el-input {
  margin-right: 12px;
}
.captchaGroup img {
  width: auto;
  height: 36px;
  border: 1px solid #c0c4cc;
  box-sizing: border-box;
  border-radius: 4px;
  cursor: pointer;
}
.btn {
  width: 100%;
}
.chunk {
  width: 320px;
  text-align: center;
  background: url("/static/images/login/chunk.png");
  background-repeat: no-repeat;
  background-size: cover;
  border-top-left-radius: 4px;
  border-bottom-left-radius: 4px;
}
.chunk span {
  color: #fff;
  display: block;
  margin-top: 45%;
  font-size: 24px;
  line-height: 36px;
}
.chunk p {
  color: #d1d1d1;
  line-height: 32px;
  font-size: 14px;
}
.footer {
  position: absolute;
  left: 0;
  bottom: 0;
  width: 100%;
  color: #99afff;
  line-height: 36px;
  text-align: center;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
}

.footer a {
  color: #99afff;
}

@media screen and (max-width: 860px) {
  .box {
    margin: 0;
    width: 100vw;
    height: 100vh;
  }

  .chunk {
    display: none;
  }
  .form {
    padding: 0;
    border-radius: 0;
  }
  .title {
    background: url("/static/images/login/mobile.png");
    background-repeat: no-repeat;
    background-size: cover;
    height: 200px;
    padding: 60px 0;
    box-sizing: border-box;
  }
  .title > span {
    color: #fff;
  }
  .footer {
    font-size: 12px;
    color: #ccc;
    
  }
  .footer a {
    color: #ccc;
  }
}
</style>
