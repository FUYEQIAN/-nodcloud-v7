<template>
  <div class="imyForm area">
    <div class="layout">
      <el-popover
        ref="searchPopover"
        popper-class="searchPopover"
        placement="bottom-start"
      >
        <el-form class="searchFrom" ref="searchFrom" inline>
          <el-form-item>
            <el-input
              placeholder="请输入单据编号"
              v-model="searchFrom.number"
              clearable
            ></el-input>
          </el-form-item>
          <el-form-item>
            <nodList
              v-model="searchFrom.account"
              placeholder="请选择资金账户"
              action="service/accountRecord"
              scene="account"
            ></nodList>
          </el-form-item>
          <el-form-item>
            <el-date-picker
              v-model="searchFrom.startTime"
              placeholder="请输入开始日期"
              value-format="yyyy-MM-dd"
              type="date"
            ></el-date-picker>
          </el-form-item>
          <el-form-item>
            <el-date-picker
              v-model="searchFrom.endTime"
              placeholder="请输入结束日期"
              value-format="yyyy-MM-dd"
              type="date"
            ></el-date-picker>
          </el-form-item>
          <el-form-item>
            <nodList
              v-model="searchFrom.user"
              placeholder="请选择制单人"
              action="service/userRecord"
              scene="user"
            ></nodList>
          </el-form-item>
          <el-form-item>
            <el-select
              v-model="searchFrom.examine"
              placeholder="请选择审核状态"
              clearable
            >
              <el-option label="未审核" :value="1"></el-option>
              <el-option label="已审核" :value="2"></el-option>
            </el-select>
          </el-form-item>
          <el-form-item>
            <nodList
              v-model="searchFrom.people"
              placeholder="请选择关联人员"
              action="service/peopleRecord"
              scene="people"
            ></nodList>
          </el-form-item>

          <el-form-item>
            <el-input
              placeholder="请输入备注信息"
              v-model="searchFrom.data"
              clearable
            ></el-input>
          </el-form-item>
          <el-divider></el-divider>
          <el-button
            class="searchBtn"
            icon="el-icon-search"
            @click="record(1)"
          ></el-button>
        </el-form>
        <el-button slot="reference" icon="el-icon-more"></el-button>
      </el-popover>
      <el-button-group>
        <template v-if="tableSelection.length != 0">
          <template v-if="$lib.getUserRoot(store.root, 'allot', 'examine')">
            <el-popover
              class="btnGroupPopover"
              type="center"
              popper-class="blockPopover"
              trigger="click"
            >
              <el-button type="info" slot="reference">操作</el-button>
              <ul>
                <li @click="examine(0)">审核</li>
                <li @click="examine(1)">反审核</li>
              </ul>
            </el-popover>
          </template>
          <template v-if="$lib.getUserRoot(store.root, 'allot', 'del')">
            <el-button @click="del(tableSelection)" type="info">删除</el-button>
          </template>
        </template>
        <template v-if="$lib.getUserRoot(store.root, 'allot', 'batch')">
          <el-button @click="batch.dialog = true">批量</el-button>
        </template>
        <el-button @click="reload">刷新</el-button>
      </el-button-group>
    </div>
    <el-divider></el-divider>
    <el-table
      :data="tableData"
      height="calc(100% - 90px)"
      @selection-change="selectionChange"
      border
      v-madeTable
    >
      <el-table-column
        type="selection"
        align="center"
        width="39px"
        fixed="left"
      ></el-table-column>
      <el-table-column
        prop="frameData.name"
        label="所属组织"
        align="center"
        width="150px"
      ></el-table-column>
      <el-table-column
        prop="time"
        label="单据时间"
        align="center"
        width="150px"
      ></el-table-column>
      <el-table-column
        prop="number"
        label="单据编号"
        align="center"
        width="200px"
      >
        <template slot-scope="scope">
          <span @click="billShow(scope.row.id)">{{ scope.row.number }}</span>
        </template>
      </el-table-column>
      <el-table-column
        prop="total"
        label="单据金额"
        align="center"
        width="150px"
      ></el-table-column>
      <el-table-column
        prop="peopleData.name"
        label="关联人员"
        align="center"
        width="150px"
      ></el-table-column>
      <el-table-column
        prop="extension.examine"
        label="审核状态"
        align="center"
        width="100px"
      ></el-table-column>
      <el-table-column
        prop="userData.name"
        label="制单人"
        align="center"
        width="150px"
      ></el-table-column>
      <el-table-column
        prop="data"
        label="备注信息 "
        align="center"
        min-width="200px"
      ></el-table-column>
      <el-table-column
        prop="set"
        label="相关操作"
        align="center"
        width="180px"
        fixed="right"
      >
        <template slot-scope="scope">
          <el-button-group>
            <el-button @click="billShow(scope.row.id)" size="mini"
              >详情</el-button
            >
            <template v-if="$lib.getUserRoot(store.root, 'allot', 'del')">
              <el-button @click="del([scope.row.id])" size="mini"
                >删除</el-button
              >
            </template>
            <el-popover
              class="btnGroupPopover"
              type="right"
              popper-class="listPopover"
              trigger="click"
              placement="bottom-end"
            >
              <el-button
                icon="el-icon-arrow-down"
                size="mini"
                slot="reference"
                style="padding: 7px"
              ></el-button>
              <ul>
                <template v-for="item in scope.row.recordData">
                  <li>
                    {{ item.extension.time }} - {{ item.userData.name }} -
                    {{ item.info }}
                  </li>
                </template>
              </ul>
            </el-popover>
          </el-button-group>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination
      class="tablePagination"
      :current-page.sync="page.current"
      :total="page.total"
      :page-size.sync="page.size"
      :page-sizes="page.sizes"
      :pager-count="page.count"
      @size-change="record(1)"
      @current-change="record(0)"
      layout="prev,pager,next,jumper,sizes,total,slot"
    >
      <PageStatus :config="page.status" :model="tableData"></PageStatus>
    </el-pagination>
    <el-dialog
      class="billDialog"
      :visible.sync="bill.dialog"
      title="单据详情"
      :fullscreen="true"
      @close="billDestroy"
    >
      <transition name="el-fade-in">
        <template v-if="bill.dialog">
          <Allot :source="bill.source" @destroy="bill.dialog = false"></Allot>
        </template>
      </transition>
    </el-dialog>
    <el-dialog
      class="tabsDialog"
      :visible.sync="batch.dialog"
      title="批量"
      width="420px"
      v-madeDialog
    >
      <transition name="el-fade-in">
        <template v-if="batch.dialog">
          <el-tabs v-model="batch.active">
            <el-tab-pane label="导入数据" name="import">
              <ul class="importTip">
                <li>1.该功能适用于Excel导入单据数据。</li>
                <li>2.录入数据时，请勿修改首行数据标题以及排序。</li>
                <li>3.字段之间存在关联关系时，将自动进行关联运算。</li>
                <li>4.请查阅使用文档获取字段格式内容以及相关导入须知。</li>
                <li>5.点击下方上传模板，选择您编辑好的模板文件即可。</li>
              </ul>
              <el-divider></el-divider>
              <el-row style="text-align: center">
                <el-col :span="12">
                  <el-button @click="downTemplate" type="info"
                    >下载模板</el-button
                  >
                </el-col>
                <el-col :span="12">
                  <el-upload
                    :action="$base.web + 'allot/import'"
                    :headers="{ Token: $store.state.token }"
                    :show-file-list="false"
                    :on-success="importCall"
                  >
                    <el-button type="primary">上传模板</el-button>
                  </el-upload>
                </el-col>
              </el-row>
            </el-tab-pane>
            <el-tab-pane class="exportGroup" label="导出数据" name="export">
              <div class="item" @click="exports('simple')">
                <i class="el-icon-download"></i>
                <p>简易报表</p>
              </div>
              <div class="item" @click="exports('detailed')">
                <i class="el-icon-download"></i>
                <p>详细报表</p>
              </div>
            </el-tab-pane>
          </el-tabs>
        </template>
      </transition>
    </el-dialog>
  </div>
</template>
<script>
import Allot from "@/views/fund/Allot";
import NodList from "@/components/lib/NodList";
import PageStatus from "@/components/lib/PageStatus";

export default {
  name: "ImyForm",
  components: {
    Allot,
    NodList,
    PageStatus,
  },
  data() {
    return {
      searchFrom: {
        number: "",
        startTime: "",
        endTime: "",
        people: null,
        user: null,
        examine: "",
        account: null,
        data: "",
      },
      tableData: [],
      tableSelection: [],
      page: {
        current: 1,
        total: 0,
        size: 30,
        sizes: [30, 60, 90, 150, 300],
        count: 5,
        status: [{ text: "总单据金额", type: "sum", key: "total" }],
      },
      bill: {
        dialog: false,
        source: null,
      },
      batch: {
        dialog: false,
        active: "import",
      },
    };
  },
  created() {
    this.init();
    this.record(1); //获取数据
  },
  computed: {
    //读取数据中心
    store() {
      return this.$store.state;
    },
  },
  methods: {
    //初始化
    init() {
      this.searchFrom.startTime = this.$moment()
        .subtract(this.store.sys.fun.days, "days")
        .format("YYYY-MM-DD");
      this.searchFrom.endTime = this.$moment().format("YYYY-MM-DD");
    },
    //获取数据
    record(page) {
      page == 0 || (this.page.current = page);
      let parm = Object.assign(
        {
          page: this.page.current,
          limit: this.page.size,
        },
        this.searchFrom
      );
      this.$axios.post("allot/record", parm).then((result) => {
        if (result.state == "success") {
          this.tableData = result.info;
          this.page.total = result.count;
          this.$refs["searchPopover"].showPopper = false;
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
    //单据详情
    billShow(id) {
      this.bill.source = id;
      this.bill.dialog = true;
    },
    //单据关闭
    billDestroy() {
      this.record(0); //获取数据
    },
    //审核单据
    examine(type) {
      let parm = [];
      this.tableData.forEach((item) => {
        if (this.tableSelection.indexOf(item.id) != -1) {
          if (item.examine == 0) {
            type == 0 && parm.push(item.id);
          } else {
            type == 1 && parm.push(item.id);
          }
        }
      });
      if (parm.length == 0) {
        this.$message({
          type: "warning",
          message: "无可操作单据!",
        });
      } else {
        this.$axios
          .post("allot/examine", {
            parm: parm,
          })
          .then((result) => {
            if (result.state == "success") {
              this.record(0); //获取数据
              this.$message({
                type: "success",
                message: "操作单据成功!",
              });
            } else if (result.state == "error") {
              this.record(0); //获取数据
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
    },
    //删除数据
    del(parm) {
      this.$confirm("您确定要删除选中数据吗?", "提示", {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning",
      }).then(() => {
        this.$axios
          .post("allot/del", {
            parm: parm,
          })
          .then((result) => {
            if (result.state == "success") {
              this.record(0);
              this.$message({
                type: "success",
                message: "删除成功!",
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
          })
          .catch(() => {});
      });
    },

    //下载模板
    downTemplate() {
      this.$message({
        type: "success",
        message: "[ 数据模板 ] 下载请求中...",
      });
      setTimeout(() => {
        window.open(this.store.base.cdnSite + "/erp/batch/转账单导入模板.xlsx");
      }, 1000);
    },
    //上传模板回调
    importCall(result, file, fileList) {
      if (result.state == "success") {
        this.$bus.emit("homeReload", this.$options.name);
        this.$message({
          type: "success",
          message: "导入单据成功!",
        });
      } else if (result.state == "error") {
        this.$message({
          type: "warning",
          message: "[ " + file.name + " ]" + result.info,
        });
      } else {
        this.$message({
          type: "error",
          message: "[ ERROR ] 服务器响应超时!",
        });
      }
    },
    //导出数据
    exports(scene) {
      if (this.tableSelection.length == 0) {
        this.$message({
          type: "warning",
          message: "未选择导出数据内容!",
        });
      } else {
        this.$message({
          type: "success",
          message: "[ 导出数据 ] 请求中...",
        });
        let parm = this.$lib.objToParm(
          { scene: scene, parm: this.tableSelection },
          true
        );
        setTimeout(() => {
          window.open(this.$base.web + "allot/exports?" + parm);
        }, 1000);
      }
    },
    //表格选中数据改变
    selectionChange(parm) {
      let data = [];
      for (let parmVo of parm) {
        data.push(parmVo.id);
      }
      this.tableSelection = data;
    },
    //页面刷新
    reload() {
      this.$bus.emit("homeReload", this.$options.name);
      this.$message({
        type: "success",
        message: "页面刷新成功!",
      });
    },
  },
};
</script>
<style>
.layout {
  display: flex;
  justify-content: space-between;
}
</style>
