<template>
  <div class="invoice area">
    <div class="layout">
      <el-popover
        ref="searchPopover"
        popper-class="searchPopover"
        placement="bottom-start"
      >
        <el-form class="searchFrom" ref="searchFrom" inline>
          <el-form-item>
            <nodList
              v-model="searchFrom.supplier"
              placeholder="请选择供应商"
              action="service/supplierRecord"
              scene="supplier"
            ></nodList>
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
            <el-input
              placeholder="请输入单据编号"
              v-model="searchFrom.number"
              clearable
            ></el-input>
          </el-form-item>
          <el-form-item>
            <el-select
              v-model="searchFrom.mold"
              placeholder="单据类型"
              multiple
              collapse-tags
            >
              <el-option label="采购单" value="buy"></el-option>
              <el-option label="采购退货单" value="bre"></el-option>
              <el-option label="销售单" value="sell"></el-option>
              <el-option label="销售退货单" value="sre"></el-option>
            </el-select>
          </el-form-item>
          <el-form-item>
            <el-date-picker
              v-model="searchFrom.startTime"
              placeholder="开具开始日期"
              value-format="yyyy-MM-dd"
              type="date"
            ></el-date-picker>
          </el-form-item>
          <el-form-item>
            <el-date-picker
              v-model="searchFrom.endTime"
              placeholder="开具结束日期"
              value-format="yyyy-MM-dd"
              type="date"
            ></el-date-picker>
          </el-form-item>
          <el-form-item>
            <el-input
              placeholder="请输入发票号码"
              v-model="searchFrom.inr"
              clearable
            ></el-input>
          </el-form-item>
          <el-form-item>
            <el-input
              placeholder="请输入发票抬头"
              v-model="searchFrom.title"
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
        <template v-if="$lib.getUserRoot(store.root, 'invoice', 'del')">
          <template v-if="tableSelection.length != 0">
            <el-button @click="del(tableSelection)" type="info">删除</el-button>
          </template>
        </template>
        <el-button @click="exports">导出</el-button>
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
      ></el-table-column>
      <el-table-column
        prop="name"
        label="单据类型"
        align="center"
        width="90px"
      ></el-table-column>
      <el-table-column
        prop="sourceData.frameData.name"
        label="所属组织"
        align="center"
        width="90px"
      ></el-table-column>
      <el-table-column
        prop="current.name"
        label="往来单位"
        align="center"
        width="120px"
      ></el-table-column>
      <el-table-column
        prop="sourceData.time"
        label="单据时间"
        align="center"
        width="120px"
      ></el-table-column>
      <el-table-column
        prop="sourceData.number"
        label="单据编号"
        align="center"
        width="180px"
      ></el-table-column>
      <el-table-column
        prop="sourceData.actual"
        label="单据金额"
        align="center"
        width="90px"
      ></el-table-column>
      <el-table-column
        prop="time"
        label="开票时间"
        align="center"
        width="100px"
      ></el-table-column>
      <el-table-column
        prop="number"
        label="发票号码"
        align="center"
        width="160px"
      ></el-table-column>
      <el-table-column
        prop="title"
        label="发票抬头"
        align="center"
        width="160px"
      ></el-table-column>
      <el-table-column
        prop="money"
        label="发票金额"
        align="center"
        width="90px"
      ></el-table-column>
      <el-table-column
        prop="file"
        label="开票附件"
        align="center"
        width="200px"
      >
        <template slot-scope="scope">
          <template v-if="scope.row.file.length == 0">
            <span>无附件</span>
          </template>
          <template v-else>
            <el-popover popper-class="listPopover" trigger="click">
              <div slot="reference">
                <span>查看附件</span>
                <i class="el-icon-arrow-down el-icon--right"></i>
              </div>
              <ul>
                <template v-for="item in scope.row.file">
                  <li @click="down(item)">{{ item.name }}</li>
                </template>
              </ul>
            </el-popover>
          </template>
        </template>
      </el-table-column>
      <el-table-column
        prop="data"
        label="备注信息"
        align="center"
        min-width="200px"
      ></el-table-column>
      <el-table-column
        prop="set"
        label="相关操作"
        align="center"
        width="120px"
        fixed="right"
      >
        <template slot-scope="scope">
          <el-button @click="del([scope.row.id])" size="mini">删除</el-button>
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
  </div>
</template>
<script>
import NodList from "@/components/lib/NodList";
import NodUpload from "@/components/lib/NodUpload";
import PageStatus from "@/components/lib/PageStatus";
export default {
  name: "Invoice",
  components: {
    NodList,
    NodUpload,
    PageStatus,
  },
  data() {
    return {
      searchFrom: {
        supplier: null,
        customer: null,
        number: "",
        mold: [],
        startTime: "",
        endTime: "",
        inr: "",
        title: "",
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
          { text: "单据总金额", type: "sum", key: "sourceData.actual" },
          { text: "发票总金额", type: "sum", key: "money" },
        ],
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
      this.$axios.post("invoice/form", parm).then((result) => {
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
    //删除数据
    del(parm) {
      this.$confirm("您确定要删除选中数据吗?", "提示", {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning",
      })
        .then(() => {
          this.$axios
            .post("invoice/del", {
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
    //下载文件
    down(file) {
      this.$message({
        type: "success",
        message: "[ " + file.name + " ] 下载请求中...",
      });
      setTimeout(() => {
        window.open(file.url);
      }, 1000);
    },
    //导出数据
    exports() {
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
        let parm = this.$lib.objToParm({ parm: this.tableSelection }, true);
        setTimeout(() => {
          window.open(this.$base.web + "invoice/formExports?" + parm);
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

<style scoped>
.layout {
  display: flex;
  justify-content: space-between;
}
</style>
