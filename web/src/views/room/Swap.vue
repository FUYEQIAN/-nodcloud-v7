<template>
  <div class="swap bill">
    <el-form
      :model="form"
      :rules="rules"
      ref="form"
      :inline="true"
      :hide-required-asterisk="true"
      style="height: 100%"
    >
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
        @setter="goods.dialog = true"
        @schemeChange="schemeChange"
        @quickEnter="quickEnter"
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
        <el-form-item label="单据成本">
          <el-input
            placeholder="请输入单据成本"
            v-model="form.total"
            :disabled="true"
          ></el-input>
        </el-form-item>
        <el-form-item label="单据费用">
          <NodIet v-model="cost" type="out" @change="costChange"></NodIet>
        </el-form-item>
        <el-form-item label="关联人员">
          <nodList
            v-model="form.people"
            placeholder="请选择关联人员"
            action="service/peopleRecord"
            scene="people"
          ></nodList>
        </el-form-item>
        <el-form-item label="物流信息">
          <Logistics v-model="form.logistics"></Logistics>
        </el-form-item>
        <el-form-item label="单据附件">
          <NodUpload
            v-model="form.file"
            :action="$base.web + 'swap/upload'"
          ></NodUpload>
        </el-form-item>
        <FieldForm
          style="display: initial"
          v-model="form.more"
          rule="swap"
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
            (form.examine == 0 && $lib.getUserRoot(store.root, 'swap', 'edit'))
          "
        >
          <el-button type="info" @click="save">保存</el-button>
        </template>
        <template v-if="form.id != 0">
          <template v-if="$lib.getUserRoot(store.root, 'swap', 'examine')">
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
    <GoodsList
      v-if="goods.dialog"
      @destroy="goodsDestroy"
      :parm="goods.parm"
      @choice="pushGrid"
    ></GoodsList>
  </div>
</template>
<script>
import { AgGridVue } from "ag-grid-vue";
import Dispose from "@/components/ag-grid/Dispose";
import Scheme from "@/components/ag-grid/Scheme";
import Storeroom from "@/components/ag-grid/Storeroom";
import Setter from "@/components/ag-grid/Setter";
import QuickInput from "@/components/ag-grid/QuickInput";
import Company from "@/components/ag-grid/Company";
import Depot from "@/components/ag-grid/Depot";
import Storehouse from "@/components/ag-grid/Storehouse";
import Batch from "@/components/ag-grid/Batch";
import Validity from "@/components/ag-grid/Validity";
import Serial from "@/components/ag-grid/Serial";
import Status from "@/components/ag-grid/Status";
import LineFeed from "@/components/lib/LineFeed";
import NodList from "@/components/lib/NodList";
import NodIet from "@/components/lib/NodIet";
import NodUpload from "@/components/lib/NodUpload";
import Logistics from "@/components/lib/Logistics";
import FieldForm from "@/components/lib/FieldForm";
import GoodsList from "@/components/dialog/GoodsList";

export default {
  name: "Swap",
  components: {
    AgGridVue,
    Status,
    LineFeed,
    NodList,
    NodIet,
    NodUpload,
    Logistics,
    FieldForm,
    GoodsList,
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
        time: "",
        number: "",
        total: 0,
        cost: 0,
        people: null,
        logistics: { key: "auto", name: "自动识别", number: "" },
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
      cost: [],
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
          row: { uniqid: this.$lib.getUniqid(), key: null },
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
          scheme: Scheme,
          storeroom: Storeroom,
          setter: Setter,
          quickInput: QuickInput,
          company: Company,
          depot: Depot,
          storehouse: Storehouse,
          batch: Batch,
          validity: Validity,
          serial: Serial,
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
            headerComponentParams: { dispose: { key: "swap" } },
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
            headerName: "商品名称",
            width: 150,
            field: "name",
            headerComponent: "scheme",
            headerComponentParams: { dispose: { title: "商品名称" } },
            editable: true,
            cellEditor: "quickInput",
            hide: false,
            dispose: true,
          },
          {
            headerName: "商品编号",
            width: 120,
            field: "number",
            hide: false,
            dispose: true,
          },
          {
            headerName: "规格型号",
            width: 120,
            field: "spec",
            hide: false,
            dispose: true,
          },
          {
            headerName: "辅助属性",
            width: 120,
            field: "attr",
            hide: false,
            dispose: true,
          },
          {
            headerName: "单位",
            width: 120,
            field: "unit",
            editable: true,
            cellEditor: "company",
            hide: false,
            dispose: true,
          },
          {
            headerName: "调出仓库",
            width: 120,
            field: "warehouse",
            headerComponent: "storeroom",
            headerComponentParams: {
              dispose: {
                title: "调出仓库",
                key: "warehouseId",
                text: "warehouse",
              },
            },
            cellRenderer: "depot",
            cellRendererParams: {
              dispose: { key: "warehouseId", text: "warehouse" },
            },
            editable: true,
            cellEditor: "storehouse",
            cellEditorParams: {
              dispose: { key: "warehouseId", text: "warehouse" },
            },
            hide: false,
            dispose: true,
          },
          {
            headerName: "调入仓库",
            width: 120,
            field: "storehouse",
            headerComponent: "storeroom",
            headerComponentParams: {
              dispose: {
                title: "调入仓库",
                key: "storehouseId",
                text: "storehouse",
              },
            },
            cellRenderer: "depot",
            cellRendererParams: {
              dispose: { key: "storehouseId", text: "storehouse" },
            },
            editable: true,
            cellEditor: "storehouse",
            cellEditorParams: {
              dispose: { key: "storehouseId", text: "storehouse" },
            },
            hide: false,
            dispose: true,
          },
          {
            headerName: "批次号",
            width: 120,
            field: "batch",
            editable: true,
            cellEditor: "batch",
            hide: true,
            dispose: false,
          },
          {
            headerName: "生产日期",
            width: 120,
            field: "mfd",
            editable: true,
            cellEditor: "validity",
            hide: true,
            dispose: false,
          },
          {
            headerName: "成本",
            width: 90,
            field: "price",
            editable: true,
            hide: false,
            dispose: true,
          },
          {
            headerName: "数量",
            width: 90,
            field: "nums",
            editable: true,
            cellEditor: "serial",
            cellEditorParams: {
              dispose: { record: { show: true, parm: { state: 0 } } },
            },
            hide: false,
            dispose: true,
          },
          {
            headerName: "总成本",
            width: 120,
            field: "total",
            hide: false,
            dispose: true,
          },
          {
            headerName: "备注信息",
            width: 120,
            field: "data",
            editable: true,
            hide: false,
            dispose: true,
          },
        ],
        status: [
          { text: "总条数", type: "count" },
          { text: "总合计", type: "sum", key: "total" },
        ],
      },
      scan: false,
      goods: {
        dialog: false,
        parm: {},
      },
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
        this.form.number = "DBD" + this.$moment().format("YYMMDDHHmmssS");
      } else {
        this.$axios.post("swap/get", { parm: this.source }).then((result) => {
          if (result.state == "success") {
            this.form = result.info.class;
            this.pushGrid(result.info.info, false);
            this.cost = result.info.cost;
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
                message: "商品信息不可为空!",
              });
            } else {
              //验证费用
              let check = this.handleCost();
              this.$axios
                .post("swap/save", {
                  class: this.form,
                  info: this.info,
                  cost: this.cost,
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
        .post("swap/examine", {
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
    //录入模式切换
    schemeChange(state) {
      this.scan = state;
      this.$grid.startEditingLastCell(this.grid.options, "name");
    },
    //快捷录入数据
    quickEnter(val, rowIndex) {
      let api = this.grid.options.api;
      if (!this.$lib.validate("empty", val)) {
        let parm = this.scan ? { code: val } : { mate: val };
        let condition = Object.assign({ page: 1, limit: 1 }, parm);
        this.$axios.post("service/goodsRecord", condition).then((result) => {
          if (result.state == "success") {
            if (result.count == 0) {
              api.startEditingCell({
                rowIndex: rowIndex,
                colKey: "name",
              });
              this.$message({
                type: "warning",
                message: "未匹配到商品数据!",
              });
            } else if (result.count == 1) {
              let source = result.info[0];
              //匹配辅助属性数量
              if (source.attr.length > 1) {
                this.goods.parm = parm;
                this.goods.dialog = true;
              } else {
                if (source.attr.length == 0) {
                  source.attr = "";
                  source.nums = 1;
                } else {
                  let attr = source.attr[0];
                  source = Object.assign({}, source, {
                    attr: attr.name,
                    buy: attr.buy,
                    sell: attr.sell,
                    retail: attr.retail,
                    code: attr.code,
                    nums: 1,
                  });
                }
                this.pushGrid([source]);
                this.scan &&
                  api.startEditingCell({
                    rowIndex: rowIndex + 1,
                    colKey: "name",
                  });
              }
            } else {
              this.goods.parm = parm;
              this.goods.dialog = true;
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
      }
    },
    //添加商品数据
    //scene:[true:常规场景|false:详情场景]
    pushGrid(items, scene = true) {
      let arr = [];
      let priceType = "buy";
      for (let item of items) {
        let goods = scene ? item : item.goodsData;
        let row = {
          uniqid: this.$lib.getUniqid(),
          key: goods.id,
          goodsType: goods.type,
          unitData: goods.units,
          unitRelation: {
            valence: goods[priceType],
            multiple: scene ? 1 : null,
          },
          warehouseId: scene ? null : item.warehouse,
          storehouseId: scene ? null : item.storehouse,
          batchType: goods.batch,
          validityType: goods.validity,
          serialType: goods.serial,
          name: goods.name,
          number: goods.number,
          spec: goods.spec,
          attr: item.attr,
          unit: scene
            ? goods.unit == "-1"
              ? "点击选择"
              : goods.unit
            : item.unit,
          warehouse: scene
            ? goods.type == 0
              ? "点击选择"
              : ""
            : this.$lib.objectSeek(item, "warehouseData|name"),
          storehouse: scene
            ? goods.type == 0
              ? "点击选择"
              : ""
            : this.$lib.objectSeek(item, "storehouseData|name"),
          batch: scene ? "" : item.batch,
          mfd: scene ? "" : item.mfd,
          price: scene ? goods[priceType] : item.price,
          nums: item.nums,
          serial: scene ? [] : item.serial,
          total: scene ? null : item.total,
          data: scene ? "" : item.data,
        };
        //数据处理
        if (scene) {
          row.total = this.$calc
            .chain(row.price)
            .multiply(row.nums)
            .round(this.store.sys.fun.digit.money)
            .done();
        } else {
          //单位关系
          if (row.unitData.length > 0) {
            let unitRelation = this.$lib.unitRelation(
              row.unit,
              row.unitData,
              this
            );
            row.unitRelation.multiple = unitRelation.multiple;
          }
        }
        arr.push(row);
      }
      this.$grid.pushGridData(this.grid.options, arr);
      this.handleGrid();
    },
    //数据表格验证
    handleGrid() {
      let effect = true;
      let data = this.$grid.getGridData(this.grid.options);
      //显示隐藏[序列号|生产日期]
      this.grid.options.columnApi.setColumnsVisible(["batch", "mfd"], false);
      data.find((item) => item.key && item.batchType) == undefined ||
        this.grid.options.columnApi.setColumnVisible("batch", true);
      data.find((item) => item.key && item.validityType) == undefined ||
        this.grid.options.columnApi.setColumnVisible("mfd", true);
      //数据处理
      let serials = [];
      for (var i = 0; i < data.length; i++) {
        if (data[i].key == null) {
          data.splice(i, 1);
        } else {
          if (data[i].unitData.length > 0 && data[i].unit == "点击选择") {
            effect == true &&
              (effect = "商品信息第" + (i + 1) + "行单位不可为空!");
            continue;
          } else if (data[i].goodsType == 0 && data[i].warehouseId == null) {
            effect == true &&
              (effect = "商品信息第" + (i + 1) + "行调出仓库不可为空!");
            continue;
          } else if (data[i].goodsType == 0 && data[i].storehouseId == null) {
            effect == true &&
              (effect = "商品信息第" + (i + 1) + "行调入仓库不可为空!");
            continue;
          } else if (
            data[i].goodsType == 0 &&
            data[i].warehouseId == data[i].storehouseId
          ) {
            effect == true &&
              (effect = "商品信息第" + (i + 1) + "行调出调入仓库不可相等!");
            continue;
          } else if (
            data[i].batchType &&
            this.$lib.validate("empty", data[i].batch)
          ) {
            effect == true &&
              (effect = "商品信息第" + (i + 1) + "行批次号不可为空!");
            continue;
          } else if (
            data[i].validityType &&
            this.$lib.validate("empty", data[i].mfd)
          ) {
            effect == true &&
              (effect = "商品信息第" + (i + 1) + "行生产日期不可为空!");
            continue;
          } else if (!this.$lib.synValidate("money", data[i].price)) {
            effect == true &&
              (effect = "商品信息第" + (i + 1) + "行成本不正确!");
            continue;
          } else if (!this.$lib.synValidate("nums", data[i].nums)) {
            effect == true &&
              (effect = "商品信息第" + (i + 1) + "行数量不正确!");
            continue;
          } else if (data[i].serialType && data[i].serial.length == 0) {
            effect == true &&
              (effect = "商品信息第" + (i + 1) + "行序列号不可为空!");
            continue;
          } else {
            data[i].total = this.$calc
              .chain(data[i].price)
              .multiply(data[i].nums)
              .round(this.store.sys.fun.digit.money)
              .done();
            this.grid.options.api.applyTransaction({ update: [data[i]] });
            serials = serials.concat(data[i].serial); //转存序列号组合
          }
        }
      }
      //场景判断
      if (effect == true) {
        //序列号重复验证
        if (serials.length != this.$lib.distinct(serials).length) {
          effect == true && (effect = "商品信息中存在重复序列号!");
        }
        //转存数据
        this.$set(
          this,
          "info",
          data.map((item) => {
            return {
              goods: item.key,
              attr: item.attr,
              unit: item.unit,
              warehouse: item.warehouseId,
              storehouse: item.storehouseId,
              batch: item.batch,
              mfd: item.mfd,
              price: item.price,
              nums: item.nums,
              serial: item.serial,
              total: item.total,
              data: item.data,
            };
          })
        );
        //汇总信息
        this.summary();
      }
      return effect;
    },
    //验证费用
    handleCost() {
      let check = true;
      let cost = this.cost;
      for (var i = 0; i < cost.length; i++) {
        if (!this.$lib.synValidate("numerical", cost[i].money)) {
          check = "单据费用第" + (i + 1) + "行金额不正确!";
          break;
        }
      }
      return check;
    },
    //费用数据改变
    costChange(cost) {
      this.form.cost = cost;
    },
    //汇总数据
    summary() {
      let total = this.$calc.chain(0);
      this.info.forEach((item) => {
        total.add(item.total);
      });
      this.form.total = total.done();
    },
    //商品层关闭
    goodsDestroy() {
      this.goods.dialog = false;
      this.goods.parm = {};
      this.$grid.startEditingLastCell(this.grid.options, "name");
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
