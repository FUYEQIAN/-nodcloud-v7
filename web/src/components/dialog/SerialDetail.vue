<template>
  <div class="serialDetail">
    <el-dialog
      :visible.sync="dialog"
      title="序列详情"
      width="600px"
      @closed="close"
      :append-to-body="true"
      v-madeDialog
    >
      <el-table :data="tableData" height="320px" border v-madeTable>
        <el-table-column
          prop="sourceData.frameData.name"
          label="所属组织"
          align="center"
          width="120px"
        ></el-table-column>
        <el-table-column
          prop="sourceData.time"
          label="操作时间"
          align="center"
          width="120px"
        ></el-table-column>
        <el-table-column
          prop="extension.type"
          label="单据类型"
          align="center"
          width="120px"
        ></el-table-column>
        <el-table-column
          prop="sourceData.number"
          label="单据编号"
          align="center"
          width="200px"
        ></el-table-column>
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
        layout="prev,pager,next,jumper,sizes,total"
      >
      </el-pagination>
      <span slot="footer" class="dialog-footer">
        <el-popover
          ref="searchPopover"
          popper-class="searchPopover"
          placement="top-start"
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
              <el-select
                v-model="searchFrom.type"
                placeholder="请选择单据类型"
                multiple
                collapse-tags
              >
                <el-option label="采购单" value="buy"></el-option>
                <el-option label="采购退货单" value="bre"></el-option>
                <el-option label="销售单" value="sell"></el-option>
                <el-option label="销售退货单" value="sre"></el-option>

                <el-option label="调拨单-出" value="swapOut"></el-option>
                <el-option label="调拨单-入" value="swapEnter"></el-option>
                <el-option label="其它入库单" value="entry"></el-option>
                <el-option label="其它出库单" value="extry"></el-option>
              </el-select>
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
            <el-divider></el-divider>
            <el-button
              class="searchBtn"
              icon="el-icon-search"
              @click="record(1)"
            ></el-button>
          </el-form>
          <el-button slot="reference" icon="el-icon-more"></el-button>
        </el-popover>
        <el-button @click="exports">导出</el-button>
      </span>
    </el-dialog>
  </div>
</template>
<script>
export default {
  name: "SerialDetail",
  props: {
    condition: {
      required: true,
      type: Object,
    },
  },
  data() {
    return {
      dialog: true,
      searchFrom: {
        number: "",
        type: [],
        startTime: "",
        endTime: "",
      },
      tableData: [],
      page: {
        current: 1,
        total: 0,
        size: 10,
        sizes: [10, 20, 50, 100, 200],
        count: 5,
      },
    };
  },
  created() {
    this.record();
  },
  computed: {
    //读取数据中心
    store() {
      return this.$store.state;
    },
  },
  methods: {
    //获取数据
    record() {
      let parm = Object.assign(
        {
          page: this.page.current,
          limit: this.page.size,
        },
        this.searchFrom,
        this.condition
      );
      this.$axios.post("serial/detailRecord", parm).then((result) => {
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
    //导出数据
    exports() {
      this.$message({
        type: "success",
        message: "[ 导出数据 ] 请求中...",
      });
      let parm = this.$lib.objToParm(
        Object.assign({}, this.searchFrom, this.condition),
        true
      );
      setTimeout(() => {
        window.open(this.$base.web + "serial/detailExports?" + parm);
      }, 1000);
    },
    //弹层关闭事件
    close() {
      this.$emit("destroy", true);
    },
  },
};
</script>
<style scoped>
/* 操作按钮浮动 */
.dialog-footer > span {
  float: left;
}
</style>
