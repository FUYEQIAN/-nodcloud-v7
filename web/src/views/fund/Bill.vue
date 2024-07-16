<template>
  <div class="bill">
    <el-form
      :model="form"
      :rules="rules"
      ref="form"
      :inline="true"
      :hide-required-asterisk="true"
      style="height: 100%"
    >
      <el-form-item
        v-show="form.type == 0 || form.type == 2 || form.type == 3"
        label="客户"
        prop="customer"
      >
        <nodList
          ref="customer"
          v-model="form.customer"
          placeholder="请选择客户"
          action="service/customerRecord"
          scene="customer"
          @change="clearGrid"
        ></nodList>
      </el-form-item>
      <el-form-item
        v-show="form.type == 1 || form.type == 2 || form.type == 4"
        label="供应商"
        prop="supplier"
      >
        <nodList
          ref="supplier"
          v-model="form.supplier"
          placeholder="请选择供应商"
          action="service/supplierRecord"
          scene="supplier"
          @change="clearGrid"
        ></nodList>
      </el-form-item>
      <el-form-item label="单据日期" prop="time">
        <el-date-picker
          v-model="form.time"
          value-format="yyyy-MM-dd"
          type="date"
          placeholder="请选择单据日期"
        ></el-date-picker>
      </el-form-item>
      <el-form-item label="单据编号" prop="number">
        <el-input
          placeholder="请输入单据编号"
          v-model="form.number"
          clearable
        ></el-input>
      </el-form-item>
      <el-divider class="fistDivider"></el-divider>
      <ag-grid-vue
        class="ag-theme-balham"
        :context="grid.context"
        :defaultColDef="grid.coldef"
        :gridOptions="grid.options"
        :columnDefs="grid.column"
        :getRowNodeId="grid.getRowNodeId"
        :frameworkComponents="grid.components"
        :tabToNextCell="$grid.gridTabToNextCell"
        @setter="showBill"
        style="height: calc(100% - 256px)"
        v-madeAgGrid
      ></ag-grid-vue>
      <Status :config="grid.status" :source="info">
        <img
          v-if="form.examine == 1"
          class="billExamine"
          src="/static/images/lib/examine.png"
        />
      </Status>
      <el-divider></el-divider>
      <LineFeed tag=".el-form-item" :rule="3">
        <el-form-item label="核销类型" prop="type">
          <el-select v-model="form.type" @change="typeChange">
            <el-option label="预收冲应收" :value="0"></el-option>
            <el-option label="预付冲应付" :value="1"></el-option>
            <el-option label="应收冲应付" :value="2"></el-option>
            <el-option label="销退冲销售" :value="3"></el-option>
            <el-option label="购退冲采购" :value="4"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="总核金额">
          <el-input v-model="form.pmy" :disabled="true"></el-input>
        </el-form-item>
        <el-form-item label="总销金额">
          <el-input v-model="form.smp" :disabled="true"></el-input>
        </el-form-item>
        <el-form-item label="关联人员">
          <nodList
            v-model="form.people"
            placeholder="请选择关联人员"
            action="service/peopleRecord"
            scene="people"
          ></nodList>
        </el-form-item>
        <el-form-item label="单据附件">
          <NodUpload
            v-model="form.file"
            :action="$base.web + 'bill/upload'"
          ></NodUpload>
        </el-form-item>
        <FieldForm
          style="display: initial"
          v-model="form.more"
          rule="bill"
          prefix="more."
        ></FieldForm>
        <el-form-item label="备注信息">
          <el-input
            placeholder="请输入备注信息"
            v-model="form.data"
            clearable
          ></el-input>
        </el-form-item>
      </LineFeed>
    </el-form>
    <div class="layerBtn">
      <el-button-group>
        <template
          v-if="
            form.id == 0 ||
            (form.examine == 0 && $lib.getUserRoot(store.root, 'bill', 'edit'))
          "
        >
          <el-button type="info" @click="save">保存</el-button>
        </template>
        <template v-if="form.id != 0">
          <template v-if="$lib.getUserRoot(store.root, 'bill', 'examine')">
            <el-button type="info" @click="examine">{{
              form.examine == 0 ? "审核" : "反审核"
            }}</el-button>
          </template>
        </template>
        <template v-if="source == null">
          <el-button type="info" @click="reload">刷新</el-button>
        </template>
      </el-button-group>
    </div>
    <BillList
      v-if="bill.dialog"
      @destroy="bill.dialog = false"
      :parm="bill.parm"
      @choice="pushGrid"
    ></BillList>
  </div>
</template>
<script>
import { AgGridVue } from "ag-grid-vue";
import Dispose from "@/components/ag-grid/Dispose";
import Setter from "@/components/ag-grid/Setter";
import Bmy from "@/components/ag-grid/Bmy";
import Status from "@/components/ag-grid/Status";
import LineFeed from "@/components/lib/LineFeed";
import NodList from "@/components/lib/NodList";
import NodUpload from "@/components/lib/NodUpload";
import FieldForm from "@/components/lib/FieldForm";
import BillList from "@/components/dialog/BillList";

export default {
  name: "Bill",
  components: {
    AgGridVue,
    Status,
    LineFeed,
    NodList,
    NodUpload,
    FieldForm,
    BillList,
  },
  props: {
    source: {
      default: null,
    },
  },
  data() {
    return {
      form: {
        id: 0,
        customer: null,
        supplier: null,
        time: "",
        number: "",
        type: 0,
        pmy: 0,
        smp: 0,
        people: null,
        file: [],
        data: "",
        examine: 0,
        more: {},
      },
      rules: {
        time: {
          required: true,
          message: "请选择单据日期",
          trigger: "change",
        },
        number: {
          required: true,
          message: "请输入单据编号",
          trigger: "blur",
        },
      },
      info: [],
      grid: {
        options: {
          onGridReady: () => {
            this.$grid.addGridRow(this.grid.options);
          },
          onCellEditingStopped: (grid) => {
            grid.context.runHandleGrid();
          },
        },
        context: {
          row: { uniqid: this.$lib.getUniqid(), key: null, sort: 5 },
          runHandleGrid: () => {
            if (this.grid.options.api == null) {
              return false;
            } else {
              this.handleGrid();
            }
          },
        },
        coldef: {
          suppressMenu: false,
          resizable: true,
          singleClickEdit: true,
          suppressMovable: true,
        },
        components: {
          dispose: Dispose,
          setter: Setter,
          bmy: Bmy,
        },
        getRowNodeId(row) {
          return row.uniqid;
        },
        column: [
          {
            headerName: "序号",
            width: 50,
            field: "sequence",
            headerComponent: "dispose",
            headerComponentParams: { dispose: { key: "ice" } },
            valueGetter: (params) => params.node.rowIndex + 1,
            hide: false,
            dispose: false,
          },
          {
            headerName: "操作",
            width: 50,
            field: "setter",
            cellRenderer: "setter",
            hide: false,
            dispose: false,
          },
          {
            headerName: "排序",
            width: 50,
            field: "sort",
            hide: true,
          },
          {
            headerName: "核销类型",
            width: 120,
            field: "bill",
            hide: false,
            dispose: true,
          },
          {
            headerName: "单据类型",
            width: 120,
            field: "mold",
            hide: false,
            dispose: true,
          },
          {
            headerName: "单据日期",
            width: 120,
            field: "time",
            hide: false,
            dispose: true,
          },
          {
            headerName: "单据编号",
            width: 150,
            field: "number",
            hide: false,
            dispose: true,
          },
          {
            headerName: "单据金额",
            width: 100,
            field: "total",
            hide: false,
            dispose: true,
          },
          {
            headerName: "已核销",
            width: 100,
            field: "amount",
            hide: false,
            dispose: true,
          },
          {
            headerName: "未核销",
            width: 100,
            field: "anwo",
            hide: false,
            dispose: true,
          },
          {
            headerName: "核销金额",
            width: 120,
            field: "money",
            headerComponent: "bmy",
            headerComponentParams: {
              dispose: {
                type: () => {
                  return this.form.type;
                },
              },
            },
            editable: true,
            hide: false,
            dispose: true,
          },
        ],
        status: [{ text: "总条数", type: "count" }],
      },
      bill: {
        dialog: false,
        parm: {},
      },

      inited: true,
    };
  },
  computed: {
    //读取数据中心
    store() {
      return this.$store.state;
    },
  },
  created() {
    this.init();
  },
  methods: {
    //初始化
    init() {
      if (this.source == null) {
        this.form.time = this.$moment().format("YYYY-MM-DD");
        this.form.number = "HXD" + this.$moment().format("YYMMDDHHmmssS");
      } else {
        this.inited = false;
        this.$axios.post("bill/get", { parm: this.source }).then((result) => {
          if (result.state == "success") {
            this.form = result.info.class;
            this.pushGrid(result.info.info, false);
            setTimeout(() => {
              this.inited = true;
            }, 996);
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
    },
    //保存单据
    save() {
      let form = this.$refs["form"];
      this.$lib
        .aloneValidate(form, ["time", "number"])
        .then(() => {
          let effect = this.handleGrid();
          if (effect == true) {
            if (this.info.length == 0) {
              this.$message({
                type: "warning",
                message: "核销单据不可为空!",
              });
            } else {
              if (this.form.pmy - 0 != this.form.smp - 0) {
                this.$message({
                  type: "warning",
                  message: "总核销金额不对等!",
                });
              } else {
                //二次验证|兼容扩展验证
                form.validate((valid) => {
                  if (valid) {
                    this.$axios
                      .post("bill/save", {
                        class: this.form,
                        info: this.info,
                      })
                      .then((result) => {
                        if (result.state == "success") {
                          if (this.store.sys.fun.examine && this.form.id == 0) {
                            this.form.id = result.info;
                            this.$message({
                              type: "success",
                              message: "审核单据中...",
                            });
                            setTimeout(() => {
                              this.examine();
                            }, 996);
                          } else {
                            this.form.id = result.info;
                            this.$message({
                              type: "success",
                              message: "单据保存成功!",
                            });
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
                  } else {
                    this.$message({
                      type: "warning",
                      message: "表单验证错误,请检查并修正!",
                    });
                  }
                });
              }
            }
          } else {
            this.$message({ type: "warning", message: effect });
          }
        })
        .catch((message) => {
          this.$message({
            type: "warning",
            message: message,
          });
        });
    },
    //审核|反审核
    examine() {
      this.$axios
        .post("bill/examine", {
          parm: [this.form.id],
        })
        .then((result) => {
          if (result.state == "success") {
            this.form.examine = this.form.examine == 0 ? 1 : 0;
            this.$message({
              type: "success",
              message:
                (this.form.examine == 0 ? "反审核" : "审核") + "单据成功!",
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
    //显示单据列表
    showBill() {
      let type = this.form.type;
      let customer = this.form.customer;
      let supplier = this.form.supplier;
      if (type == 0 && customer == null) {
        this.$message({ type: "warning", message: "客户不可为空!" });
      } else if (type == 1 && supplier == null) {
        this.$message({ type: "warning", message: "供应商不可为空!" });
      } else if (type == 2 && (customer == null || supplier == null)) {
        this.$message({ type: "warning", message: "客户和供应商不可为空!" });
      } else if (type == 3 && customer == null) {
        this.$message({ type: "warning", message: "客户不可为空!" });
      } else if (type == 4 && supplier == null) {
        this.$message({ type: "warning", message: "供应商不可为空!" });
      } else {
        this.bill.parm = {
          type: type,
          customer: customer,
          supplier: supplier,
        };
        this.bill.dialog = true;
      }
    },
    //模式切换
    typeChange() {
      if (this.inited) {
        this.$refs["customer"].init();
        this.$refs["supplier"].init();
        this.clearGrid();
      }
    },
    //清空表格
    clearGrid() {
      this.inited && this.grid.options.api.setRowData([this.grid.context.row]);
    },
    //添加商品数据
    //scene:[true:常规场景|false:详情场景]
    pushGrid(items, scene = true) {
      let arr = [];
      let grid = this.$grid.getGridData(this.grid.options);
      let obj = [
        { imy: 0, sell: 1, sre: 2, ice: 3 },
        { omy: 0, buy: 1, bre: 2, oce: 3 },
        { sell: 0, sre: 1, ice: 2, buy: 3, bre: 4, oce: 5 },
        { sre: 0, sell: 1 },
        { bre: 0, buy: 1 },
      ][this.form.type];
      for (let item of items) {
        let source = scene ? item : item.sourceData;
        let row = {
          uniqid: this.$lib.getUniqid(),
          key: source.id,
          billType: scene ? source.bill.type : item.bill,
          moldType: scene ? source.mold.type : item.mold,
          sort: obj[scene ? source.mold.type : item.mold],
          bill: scene ? source.bill.name : item.extension.bill,
          mold: scene ? source.mold.name : item.extension.mold,
          time: source.time,
          number: source.number,
          total: source.total,
          amount: scene ? source.extension.amount : source.extension.amount,
          anwo: scene ? source.extension.anwo : source.extension.anwo,
          money: scene ? "" : item.money,
        };
        //类型转换
        if (
          [0, 1, 2].indexOf(this.form.type) != -1 &&
          ["bre", "sre"].indexOf(row.moldType) != -1
        ) {
          row.total *= -1;
          row.amount *= -1;
          row.anwo *= -1;
        }
        //数据去重
        if (
          grid.findIndex(
            (g) => g.key == row.key && g.moldType == row.moldType
          ) == -1
        ) {
          arr.push(row);
        }
      }
      //场景判断
      this.$grid.pushGridData(this.grid.options, arr);
      //单据排序
      this.grid.options.columnApi.applyColumnState({
        state: [{ colId: "sort", sort: "asc" }],
      });
      //数据验证
      this.handleGrid();
    },
    //数据表格验证
    handleGrid() {
      let effect = true;
      let data = this.$grid.getGridData(this.grid.options);
      //数据处理
      for (var i = 0; i < data.length; i++) {
        if (data[i].key == null) {
          data.splice(i, 1);
        } else {
          if (
            !this.$lib.synValidate("numerical", data[i].money) ||
            data[i].money - 0 == 0
          ) {
            effect == true &&
              (effect = "数据表格第" + (i + 1) + "行核销金额不正确!");
            continue;
          } else if (
            data[i].total - 0 > 0 &&
            !(data[i].money > 0 && data[i].money <= data[i].anwo)
          ) {
            effect == true &&
              (effect = "数据表格第" + (i + 1) + "行核销金额不正确!");
            continue;
          } else if (
            data[i].total - 0 < 0 &&
            !(data[i].money < 0 && data[i].money >= data[i].anwo)
          ) {
            effect == true &&
              (effect = "数据表格第" + (i + 1) + "行核销金额不正确!");
            continue;
          }
        }
      }
      //场景判断
      if (effect == true) {
        //转存数据
        this.$set(
          this,
          "info",
          data.map((item) => {
            return {
              source: item.key,
              bill: item.billType,
              mold: item.moldType,
              money: item.money,
            };
          })
        );
        //汇总信息
        this.summary();
      }
      return effect;
    },
    //汇总数据
    summary() {
      let pmy = 0;
      let smp = 0;
      // 核销对应|0核|1销
      let obj = [
        { imy: 0, sell: 1, sre: 1, ice: 1 },
        { omy: 0, buy: 1, bre: 1, oce: 1 },
        { sell: 0, sre: 0, ice: 0, buy: 1, bre: 1, oce: 1 },
        { sre: 0, sell: 1 },
        { bre: 0, buy: 1 },
      ][this.form.type];
      this.info.forEach((item) => {
        if (obj[item.mold] == 0) {
          pmy = this.$calc.chain(pmy).add(item.money).done();
        } else {
          smp = this.$calc.chain(smp).add(item.money).done();
        }
      });
      this.form.pmy = pmy;
      this.form.smp = smp;
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
