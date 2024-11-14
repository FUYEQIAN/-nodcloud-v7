<template>
  <div class="sorForm area">
    <div class="layout">
      <el-popover
        ref="searchPopover"
        popper-class="searchPopover"
        placement="bottom-start"
      >
        <el-form class="searchFrom" ref="searchFrom" inline>
          <el-form-item>
            <el-input
              placeholder="请输入商品名称"
              v-model="searchFrom.goods"
              clearable
            ></el-input>
          </el-form-item>
          <el-form-item>
            <el-input
              placeholder="请输入单据编号"
              v-model="searchFrom.number"
              clearable
            ></el-input>
          </el-form-item>
          <el-form-item>
            <nodList
              v-model="searchFrom.customer"
              placeholder="请选择客户"
              action="service/customerRecord"
              scene="customer"
            ></nodList>
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
            <el-date-picker
              v-model="searchFrom.startTime"
              placeholder="单据开始日期"
              value-format="yyyy-MM-dd"
              type="date"
            ></el-date-picker>
          </el-form-item>
          <el-form-item>
            <el-date-picker
              v-model="searchFrom.endTime"
              placeholder="单据结束日期"
              value-format="yyyy-MM-dd"
              type="date"
            ></el-date-picker>
          </el-form-item>
          <el-form-item>
            <el-date-picker
              v-model="searchFrom.startArrival"
              placeholder="到货开始日期"
              value-format="yyyy-MM-dd"
              type="date"
            ></el-date-picker>
          </el-form-item>
          <el-form-item>
            <el-date-picker
              v-model="searchFrom.endArrival"
              placeholder="到货结束日期"
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
            <el-select
              v-model="searchFrom.state"
              placeholder="请选择出库状态"
              clearable
            >
              <el-option label="未出库" :value="1"></el-option>
              <el-option label="部分出库" :value="2"></el-option>
              <el-option label="已出库" :value="3"></el-option>
              <el-option label="关闭" :value="4"></el-option>
            </el-select>
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
          <template v-if="$lib.getUserRoot(store.root, 'sor', 'examine')">
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
          <template v-if="$lib.getUserRoot(store.root, 'sor', 'del')">
            <el-button @click="del(tableSelection)" type="info">删除</el-button>
          </template>
        </template>
        <template v-if="$lib.getUserRoot(store.root, 'sor', 'batch')">
          <el-button @click="batch.dialog = true">批量</el-button>
        </template>
        <el-button @click="reload">刷新</el-button>
      </el-button-group>
    </div>
    <el-divider></el-divider>
    <el-table
      :data="tableData"
      height="calc(100% - 90px)"
      @sort-change="sortTableFun"
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
        width="120px"
      ></el-table-column>
      <el-table-column
        prop="customerData.name"
        label="客户"
        align="center"
        width="160px"
      ></el-table-column>
      <el-table-column
        prop="time"
        label="单据时间"
        align="center"
        width="120px"
        sortable
      ></el-table-column>
      <el-table-column
        prop="number"
        label="单据编号"
        align="center"
        width="200px"
        sortable
      >
        <template slot-scope="scope">
          <template v-if="scope.row.relation.length == 0">
            <span @click="billShow(scope.row.id)">{{ scope.row.number }}</span>
          </template>
          <template v-else>
            <el-popover popper-class="tablePopover" trigger="click">
              <div slot="reference">
                <span @click="billShow(scope.row.id)">{{
                  scope.row.number
                }}</span>
                <i class="el-icon-arrow-down el-icon--right"></i>
              </div>
              <el-table
                :data="scope.row.relation"
                height="120px"
                size="mini"
                border
              >
                <el-table-column
                  prop="type"
                  label="单据类型"
                  align="center"
                  width="90px"
                ></el-table-column>
                <el-table-column
                  prop="time"
                  label="单据时间"
                  align="center"
                  width="90px"
                ></el-table-column>
                <el-table-column
                  prop="number"
                  label="单据编号"
                  align="center"
                  width="160px"
                >
                  <template slot-scope="scope">
                    <span @click="open(scope.row)">{{ scope.row.number }}</span>
                  </template>
                </el-table-column>
              </el-table>
            </el-popover>
          </template>
        </template>
      </el-table-column>
      <el-table-column
        prop="total"
        label="单据金额"
        align="center"
        width="120px"
        sortable
      ></el-table-column>
      <el-table-column
        prop="actual"
        label="实际金额"
        align="center"
        width="120px"
        sortable
      ></el-table-column>
      <el-table-column
        prop="arrival"
        label="到货日期"
        align="center"
        width="120px"
        sortable
      ></el-table-column>
      <el-table-column
        prop="peopleData.name"
        label="关联人员"
        align="center"
        width="120px"
      ></el-table-column>
      <el-table-column
        prop="extension.examine"
        label="审核状态"
        align="center"
        width="100px"
      ></el-table-column>
      <el-table-column
        prop="extension.state"
        label="出库状态"
        align="center"
        width="100px"
      ></el-table-column>
      <el-table-column
        prop="userData.name"
        label="制单人"
        align="center"
        width="120px"
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
            <template v-if="$lib.getUserRoot(store.root, 'sor', 'del')">
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
          <Sor :source="bill.source" @destroy="bill.dialog = false"></Sor>
        </template>
      </transition>
    </el-dialog>
    <el-dialog
      v-if="bills.type == 'bor'"
      class="billDialog"
      :visible.sync="bills.dialog"
      title="采购订单详情"
      :fullscreen="true"
    >
      <transition name="el-fade-in">
        <template v-if="bills.dialog">
          <Bor :source="bills.source" @destroy="bills.dialog = false"></Bor>
        </template>
      </transition>
    </el-dialog>
    <el-dialog
      v-if="bills.type == 'sell'"
      class="billDialog"
      :visible.sync="bills.dialog"
      title="销售单详情"
      :fullscreen="true"
    >
      <transition name="el-fade-in">
        <template v-if="bills.dialog">
          <Sell :source="bills.source" @destroy="bills.dialog = false"></Sell>
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
                    :action="$base.web + 'sor/import'"
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
import Sor from "@/views/sale/Sor";
import Sell from "@/views/sale/Sell";
import Bor from "@/views/purchase/Bor";
import NodList from "@/components/lib/NodList";
import PageStatus from "@/components/lib/PageStatus";

export default {
  name: "SorForm",
  components: {
    Sor,
    Sell,
    Bor,
    NodList,
    PageStatus,
  },
  data() {
    return {
      searchFrom: {
        goods: "",
        number: "",
        customer: null,
        people: null,
        startTime: "",
        endTime: "",
        startArrival: "",
        endArrival: "",
        user: null,
        examine: "",
        state: "",
        data: "",
      },
      columns:{
        column:'',
        order:''
      },
      tableData: [],
      tableSelection: [],
      page: {
        current: 1,
        total: 0,
        size: 30,
        sizes: [30, 60, 90, 150, 300],
        count: 5,
        status: [
          { text: "总单据金额", type: "sum", key: "total" },
          { text: "总实际金额", type: "sum", key: "actual" },
        ],
      },
      bill: {
        dialog: false,
        source: null,
      },
      bills: {
        type: null,
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
    //列表排序
    sortTableFun(column) {
        this.columns.column = column.prop; //该方法获取到当前列绑定的prop字段名赋值给一个变量，之后这个变量做为入参传给后端
        if (column.prop) {
            //该列有绑定prop字段走这个分支
            if (column.order == 'ascending') {
                //当用户点击的是升序按钮，即ascending时
                this.columns.order = 'asc'; //将order这个变量赋值为后端接口文档定义的升序的字段名，之后作为入参传给后端
            } else if (column.order == 'descending') {
                //当用户点击的是升序按钮，即descending时
                this.columns.order = 'desc'; //将order这个变量赋值为后端接口文档定义的降序的字段名，之后作为入参传给后端
            }
        }
        this.record(1);
       
    },
    //获取数据
    record(page) {
      page == 0 || (this.page.current = page);
      let parm = Object.assign(
        {
          page: this.page.current,
          limit: this.page.size,
        },
        this.columns,
        this.searchFrom
      );
      this.$axios.post("sor/record", parm).then((result) => {
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
          .post("sor/examine", {
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
      })
        .then(() => {
          this.$axios
            .post("sor/del", {
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
            });
        })
        .catch(() => {});
    },

    //下载模板
    downTemplate() {
      this.$message({
        type: "success",
        message: "[ 数据模板 ] 下载请求中...",
      });
      setTimeout(() => {
        window.open(
          this.store.base.cdnSite + "/erp/batch/销售订单导入模板.xlsx"
        );
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
          window.open(this.$base.web + "sor/exports?" + parm);
        }, 1000);
      }
    },
    //关联订单页面切换
    open(parm) {
      this.bills.type = parm.types;
      this.bills.source = parm.id;
      this.bills.dialog = true;
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
