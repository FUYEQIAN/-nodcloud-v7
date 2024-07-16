<template>
  <div class="slt area">
    <div class="layout">
      <el-popover
        ref="searchPopover"
        popper-class="searchPopover"
        placement="bottom-start"
      >
        <el-form class="searchFrom" ref="searchFrom" inline>
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
            <nodList
              v-model="searchFrom.user"
              placeholder="请选择制单人"
              action="service/userRecord"
              scene="user"
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
            <el-select
              v-model="searchFrom.mold"
              placeholder="单据类型"
              multiple
              collapse-tags
            >
              <el-option label="销售单" value="sell"></el-option>
              <el-option label="销售退货单" value="sre"></el-option>
            </el-select>
          </el-form-item>
          <el-form-item>
            <el-select v-model="searchFrom.type">
              <el-option label="隐藏明细" :value="0"></el-option>
              <el-option label="显示明细" :value="1"></el-option>
            </el-select>
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
        <el-button @click="exports">导出</el-button>
        <el-button @click="reload">刷新</el-button>
      </el-button-group>
    </div>
    <el-divider></el-divider>
    <el-table
      ref="table"
      :data="tableData"
      :tree-props="{ children: 'node' }"
      row-key="key"
      height="calc(100% - 90px)"
      border
      v-madeTable
    >
      <template v-if="tableType == 1">
        <el-table-column align="center" width="44px"></el-table-column>
      </template>
      <el-table-column
        prop="bill"
        label="单据类型"
        align="center"
        width="90px"
      ></el-table-column>
      <el-table-column
        prop="frameData.name"
        label="所属组织"
        align="center"
        width="90px"
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
      ></el-table-column>
      <el-table-column
        prop="number"
        label="单据编号"
        align="center"
        width="180px"
      ></el-table-column>
      <template v-if="tableType == 1">
        <el-table-column
          prop="detail.name"
          label="商品名称"
          align="center"
          width="120px"
        ></el-table-column>
        <el-table-column
          prop="detail.attr"
          label="辅助属性"
          align="center"
          width="120px"
        ></el-table-column>
        <el-table-column
          prop="detail.unit"
          label="单位"
          align="center"
          width="100px"
        ></el-table-column>
        <el-table-column
          prop="detail.price"
          label="单价"
          align="center"
          width="100px"
        ></el-table-column>
        <el-table-column
          prop="detail.nums"
          label="数量"
          align="center"
          width="100px"
        ></el-table-column>
        <el-table-column
          prop="detail.dsc"
          label="折扣额"
          align="center"
          width="100px"
        ></el-table-column>
        <el-table-column
          prop="detail.total"
          label="金额"
          align="center"
          width="100px"
        ></el-table-column>
        <el-table-column
          prop="detail.tat"
          label="税额"
          align="center"
          width="100px"
        ></el-table-column>
        <el-table-column
          prop="detail.tpt"
          label="价税合计"
          align="center"
          width="100px"
        ></el-table-column>
      </template>
      <el-table-column
        prop="total"
        label="单据金额"
        align="center"
        width="120px"
      ></el-table-column>
      <el-table-column
        prop="discount"
        label="优惠金额"
        align="center"
        width="120px"
      ></el-table-column>
      <el-table-column
        prop="actual"
        label="实际金额"
        align="center"
        width="120px"
      ></el-table-column>
      <el-table-column
        prop="act"
        label="成本"
        align="center"
        width="120px"
      ></el-table-column>
      <el-table-column
        prop="gpt"
        label="毛利润"
        align="center"
        width="120px"
      ></el-table-column>
      <el-table-column
        prop="gpr"
        label="毛利率"
        align="center"
        width="120px"
      ></el-table-column>
      <el-table-column
        prop="cost"
        label="单据费用"
        align="center"
        width="120px"
      ></el-table-column>
      <el-table-column
        prop="npt"
        label="净利润"
        align="center"
        width="120px"
      ></el-table-column>
      <el-table-column
        prop="npr"
        label="净利率"
        align="center"
        width="120px"
      ></el-table-column>
      <el-table-column
        prop="extension.amount"
        label="核销金额"
        align="center"
        width="120px"
      ></el-table-column>
      <el-table-column
        prop="extension.nucleus"
        label="核销状态"
        align="center"
        width="120px"
      ></el-table-column>
      <el-table-column
        prop="userData.name"
        label="制单人"
        align="center"
        width="120px"
      ></el-table-column>
      <el-table-column
        prop="peopleData.name"
        label="关联人员"
        align="center"
        width="120px"
      ></el-table-column>
      <el-table-column
        prop="data"
        label="备注信息"
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
    ></el-pagination>
  </div>
</template>
<script>
import NodList from "@/components/lib/NodList";
export default {
  name: "Slt",
  components: {
    NodList,
  },
  data() {
    return {
      searchFrom: {
        customer: null,
        number: "",
        startTime: "",
        endTime: "",
        user: null,
        people: null,
        mold: [],
        type: 0,
      },
      tableData: [],
      tableType: 0,
      page: {
        current: 1,
        total: 0,
        size: 30,
        sizes: [30, 60, 90, 150, 300],
        count: 5,
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
      this.$axios.post("mrt/mpt", parm).then((result) => {
        if (result.state == "success") {
          this.tableData = result.info;
          this.page.total = result.count;
          this.$refs["searchPopover"].showPopper = false;
          //ELEMENT更新错位.暂延时处理
          this.tableType = -1;
          this.$nextTick(() => {
            this.tableType = [0, 1][this.searchFrom.type];
            setTimeout(() => {
              this.$refs.table.doLayout();
            }, 0);
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
    //导出数据
    exports() {
      this.$message({
        type: "success",
        message: "[ 导出数据 ] 请求中...",
      });
      let parm = this.$lib.objToParm(this.searchFrom, true);
      setTimeout(() => {
        window.open(this.$base.web + "mrt/mptExports?" + parm);
      }, 1000);
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
.el-table >>> .el-table__row--level-1 {
  background: #fcfcfc;
}
</style>
