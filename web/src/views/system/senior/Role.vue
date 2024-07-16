<template>
  <div class="role area">
    <div class="layout">
      <el-popover
        ref="searchPopover"
        popper-class="searchPopover"
        placement="bottom-start"
      >
        <el-form class="searchFrom" ref="searchFrom" inline>
          <el-form-item>
            <el-input
              placeholder="请输入角色名称"
              v-model="searchFrom.name"
              clearable
            ></el-input>
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
        <template v-if="$lib.getUserRoot(store.root, 'senior', 'add')">
          <el-button @click="set(0)">新增</el-button>
        </template>
        <el-button @click="reload">刷新</el-button>
      </el-button-group>
    </div>
    <el-divider></el-divider>
    <el-table :data="tableData" height="calc(100% - 90px)" border>
      <el-table-column
        prop="name"
        label="角色名称"
        align="center"
        width="200px"
      ></el-table-column>
      <el-table-column
        prop="data"
        label="备注信息 "
        align="center"
        width="200px"
      ></el-table-column>
      <el-table-column prop="set" label="相关操作" align="center" width="160px">
        <template slot-scope="scope">
          <el-button-group>
            <el-button @click="set(scope.row.id)" size="mini">详情</el-button>
            <template v-if="$lib.getUserRoot(store.root, 'senior', 'del')">
              <el-button @click="del(scope.row.id)" size="mini">删除</el-button>
            </template>
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
      layout="prev,pager,next,jumper,sizes,total"
    >
    </el-pagination>
    <el-dialog
      :visible.sync="dialog.show"
      title="详情"
      width="608px"
      v-madeDialog
    >
      <transition name="el-fade-in">
        <template v-if="dialog.show">
          <el-tabs v-model="dialog.active">
            <el-tab-pane label="基础资料" name="base">
              <el-form
                :model="form"
                :rules="rules"
                ref="form"
                label-width="80px"
              >
                <el-form-item label="角色名称" prop="name">
                  <el-input
                    placeholder="请输入角色名称"
                    v-model="form.name"
                  ></el-input>
                </el-form-item>
                <el-form-item label="备注信息">
                  <el-input
                    placeholder="请输入备注信息"
                    v-model="form.data"
                  ></el-input>
                </el-form-item>
              </el-form>
            </el-tab-pane>
            <el-tab-pane label="功能权限" name="root">
              <el-table :data="form.root" border v-madeTable>
                <el-table-column
                  prop="name"
                  label="模块名称"
                  align="center"
                  min-width="100px"
                  fixed="left"
                ></el-table-column>
                <el-table-column label="新增" align="center" width="70px">
                  <template slot-scope="scope">
                    <template v-if="scope.row.data.add == undefined"
                      >-</template
                    >
                    <template v-else>
                      <el-checkbox v-model="scope.row.data.add"></el-checkbox>
                    </template>
                  </template>
                </el-table-column>
                <el-table-column label="删除" align="center" width="70px">
                  <template slot-scope="scope">
                    <template v-if="scope.row.data.del == undefined"
                      >-</template
                    >
                    <template v-else>
                      <el-checkbox v-model="scope.row.data.del"></el-checkbox>
                    </template>
                  </template>
                </el-table-column>
                <el-table-column label="修改" align="center" width="70px">
                  <template slot-scope="scope">
                    <template v-if="scope.row.data.edit == undefined"
                      >-</template
                    >
                    <template v-else>
                      <el-checkbox v-model="scope.row.data.edit"></el-checkbox>
                    </template>
                  </template>
                </el-table-column>
                <el-table-column label="查看" align="center" width="70px">
                  <template slot-scope="scope">
                    <template v-if="scope.row.data.see == undefined"
                      >-</template
                    >
                    <template v-else>
                      <el-checkbox v-model="scope.row.data.see"></el-checkbox>
                    </template>
                  </template>
                </el-table-column>
                <el-table-column label="批量" align="center" width="70px">
                  <template slot-scope="scope">
                    <template v-if="scope.row.data.batch == undefined"
                      >-</template
                    >
                    <template v-else>
                      <el-checkbox v-model="scope.row.data.batch"></el-checkbox>
                    </template>
                  </template>
                </el-table-column>
                <el-table-column
                  label="审核|反审核"
                  align="center"
                  width="90px"
                >
                  <template slot-scope="scope">
                    <template v-if="scope.row.data.examine == undefined"
                      >-</template
                    >
                    <template v-else>
                      <el-checkbox
                        v-model="scope.row.data.examine"
                      ></el-checkbox>
                    </template>
                  </template>
                </el-table-column>
              </el-table>
            </el-tab-pane>
            <el-tab-pane label="数据权限" name="auth">
              <el-form :model="form.auth" label-width="80px">
                <el-form-item label="组织机构">
                  <nodTree
                    v-model="form.auth.frame"
                    :treeData="treeData"
                    :checkStrictly="true"
                    placeholder="请选择组织机构"
                    style="display: inline-block"
                  ></nodTree>
                </el-form-item>
                <el-form-item label="客户">
                  <el-select
                    v-model="form.auth.customer"
                    placeholder="请选择客户数据"
                  >
                    <el-option label="全部数据" value="all"></el-option>
                    <el-option label="用户数据" value="userId"></el-option>
                    <el-option label="组织数据" value="roleFrame"></el-option>
                  </el-select>
                </el-form-item>
                <el-form-item label="供应商">
                  <el-select
                    v-model="form.auth.supplier"
                    placeholder="请选择供应商数据"
                  >
                    <el-option label="全部数据" value="all"></el-option>
                    <el-option label="用户数据" value="userId"></el-option>
                    <el-option label="组织数据" value="roleFrame"></el-option>
                  </el-select>
                </el-form-item>
                <el-form-item label="仓库">
                  <el-select
                    v-model="form.auth.warehouse"
                    placeholder="请选择仓库数据"
                  >
                    <el-option label="全部数据" value="all"></el-option>
                    <el-option label="用户数据" value="userFrame"></el-option>
                    <el-option label="组织数据" value="roleFrame"></el-option>
                  </el-select>
                </el-form-item>
                <el-form-item label="资金账户">
                  <el-select
                    v-model="form.auth.account"
                    placeholder="请选择资金账户数据"
                  >
                    <el-option label="全部数据" value="all"></el-option>
                    <el-option label="用户数据" value="userFrame"></el-option>
                    <el-option label="组织数据" value="roleFrame"></el-option>
                  </el-select>
                </el-form-item>
                <el-form-item label="用户">
                  <el-select
                    v-model="form.auth.user"
                    placeholder="请选择用户数据"
                  >
                    <el-option label="全部数据" value="all"></el-option>
                    <el-option label="用户数据" value="userId"></el-option>
                    <el-option label="组织数据" value="roleFrame"></el-option>
                  </el-select>
                </el-form-item>
                <el-form-item label="人员">
                  <el-select
                    v-model="form.auth.people"
                    placeholder="请选择人员数据"
                  >
                    <el-option label="全部数据" value="all"></el-option>
                    <el-option label="用户数据" value="userFrame"></el-option>
                    <el-option label="组织数据" value="roleFrame"></el-option>
                  </el-select>
                </el-form-item>
              </el-form>
            </el-tab-pane>
          </el-tabs>
        </template>
      </transition>
      <span slot="footer" class="dialog-footer">
        <el-button @click="dialog.show = false">取消</el-button>
        <template
          v-if="form.id == 0 || $lib.getUserRoot(store.root, 'senior', 'edit')"
        >
          <el-button @click="save" type="primary">保存</el-button>
        </template>
      </span>
    </el-dialog>
  </div>
</template>
<script>
import NodTree from "@/components/lib/NodTree";
export default {
  name: "Role",
  components: {
    NodTree,
  },
  data() {
    return {
      searchFrom: {
        name: "",
        data: "",
      },
      tableData: [],
      page: {
        current: 1,
        total: 0,
        size: 30,
        sizes: [30, 60, 90, 150, 300],
        count: 5,
      },
      frame: [],
      dialog: {
        show: false,
        active: "base",
      },
      form: {
        id: 0,
        name: "",
        data: "",
        root: [
          {
            name: "采购订单",
            module: "bor",
            data: {
              add: true,
              del: true,
              edit: true,
              see: true,
              batch: true,
              examine: true,
            },
          },
          {
            name: "采购单",
            module: "buy",
            data: {
              add: true,
              del: true,
              edit: true,
              see: true,
              batch: true,
              examine: true,
            },
          },
          {
            name: "采购退货单",
            module: "bre",
            data: {
              add: true,
              del: true,
              edit: true,
              see: true,
              batch: true,
              examine: true,
            },
          },
          {
            name: "销售订单",
            module: "sor",
            data: {
              add: true,
              del: true,
              edit: true,
              see: true,
              batch: true,
              examine: true,
            },
          },
          {
            name: "销售单",
            module: "sell",
            data: {
              add: true,
              del: true,
              edit: true,
              see: true,
              batch: true,
              examine: true,
            },
          },
          {
            name: "销售退货单",
            module: "sre",
            data: {
              add: true,
              del: true,
              edit: true,
              see: true,
              batch: true,
              examine: true,
            },
          },

          {
            name: "库存查询",
            module: "stock",
            data: {
              see: true,
            },
          },
          {
            name: "批次查询",
            module: "batch",
            data: {
              see: true,
            },
          },
          {
            name: "序列查询",
            module: "serial",
            data: {
              see: true,
            },
          },
          {
            name: "库存盘点",
            module: "inventory",
            data: {
              see: true,
            },
          },
          {
            name: "调拨单",
            module: "swap",
            data: {
              add: true,
              del: true,
              edit: true,
              see: true,
              batch: true,
              examine: true,
            },
          },
          {
            name: "其它入库单",
            module: "entry",
            data: {
              add: true,
              del: true,
              edit: true,
              see: true,
              batch: true,
              examine: true,
            },
          },
          {
            name: "其它出库单",
            module: "extry",
            data: {
              add: true,
              del: true,
              edit: true,
              see: true,
              batch: true,
              examine: true,
            },
          },
          {
            name: "收款单",
            module: "imy",
            data: {
              add: true,
              del: true,
              edit: true,
              see: true,
              batch: true,
              examine: true,
            },
          },
          {
            name: "付款单",
            module: "omy",
            data: {
              add: true,
              del: true,
              edit: true,
              see: true,
              batch: true,
              examine: true,
            },
          },
          {
            name: "核销单",
            module: "bill",
            data: {
              add: true,
              del: true,
              edit: true,
              see: true,
              batch: true,
              examine: true,
            },
          },
          {
            name: "转账单",
            module: "allot",
            data: {
              add: true,
              del: true,
              edit: true,
              see: true,
              batch: true,
              examine: true,
            },
          },
          {
            name: "其它收入单",
            module: "ice",
            data: {
              add: true,
              del: true,
              edit: true,
              see: true,
              batch: true,
              examine: true,
            },
          },
          {
            name: "其它支出单",
            module: "oce",
            data: {
              add: true,
              del: true,
              edit: true,
              see: true,
              batch: true,
              examine: true,
            },
          },
          {
            name: "购销费用",
            module: "cost",
            data: {
              edit: true,
              see: true,
              batch: true,
            },
          },
          {
            name: "购销发票",
            module: "invoice",
            data: {
              del: true,
              edit: true,
              see: true,
              batch: true,
            },
          },
          {
            name: "采购报表",
            module: "brt",
            data: {
              see: true,
            },
          },
          {
            name: "销售报表",
            module: "srt",
            data: {
              see: true,
            },
          },
          {
            name: "销售报表",
            module: "mrt",
            data: {
              see: true,
            },
          },
          {
            name: "仓库报表",
            module: "wrf",
            data: {
              see: true,
            },
          },
          {
            name: "资金报表",
            module: "crt",
            data: {
              see: true,
            },
          },
          {
            name: "基础资料",
            module: "base",
            data: {
              add: true,
              del: true,
              edit: true,
              see: true,
              batch: true,
            },
          },
          {
            name: "辅助资料",
            module: "assist",
            data: {
              add: true,
              del: true,
              edit: true,
              see: true,
              batch: true,
            },
          },
          {
            name: "高级设置",
            module: "senior",
            data: {
              add: true,
              del: true,
              edit: true,
              see: true,
            },
          },
        ],
        auth: {
          frame: [-2],
          customer: "all",
          supplier: "all",
          warehouse: "all",
          account: "all",
          user: "all",
          people: "all",
        },
      },
      rules: {
        name: {
          required: true,
          message: "请输入角色名称",
          trigger: "blur",
        },
      },
    };
  },
  created() {
    this.getFrame(); //获取组织数据
    this.record(1); //获取数据
  },
  computed: {
    //读取数据中心
    store() {
      return this.$store.state;
    },
    treeData() {
      return [
        {
          id: -2,
          name: "全部数据",
          sub: [],
        },
        {
          id: -1,
          name: "用户数据",
          sub: [],
        },
        {
          id: 0,
          name: "默认组织",
          sub: this.frame,
        },
      ];
    },
  },
  methods: {
    //获取组织数据
    getFrame() {
      this.$axios.post("frame/record").then((result) => {
        if (result.state == "success") {
          this.frame = result.info;
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
      this.$axios.post("role/record", parm).then((result) => {
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
    //设置数据
    set(id) {
      //1.初始化数据
      this.form = this.$lib.extend(true, {}, this.$options.data().form);
      //2.请求数据
      if (id > 0) {
        this.$axios
          .post("role/get", {
            id: id,
          })
          .then((result) => {
            if (result.state == "success") {
              this.form = result.info;
              this.dialog.show = true; //显示弹层
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
        this.dialog.show = true; //显示弹层
      }
    },
    //保存数据
    save() {
      this.$refs["form"].validate((valid) => {
        if (valid) {
          let frame = this.form.auth.frame;
          if (frame.length == 0) {
            this.$message({
              type: "warning",
              message: "组织机构不可为空!",
            });
          } else if (
            frame.length != 1 &&
            (frame.indexOf(-2) != -1 || frame.indexOf(-1) != -1)
          ) {
            this.$message({
              type: "warning",
              message: "组织机构[ 全部数据 - 用户数据 - 默认组织]为互拆关系!",
            });
          } else {
            this.$axios.post("role/save", this.form).then((result) => {
              if (result.state == "success") {
                this.record(0);
                this.dialog.show = false;
                this.$message({
                  type: "success",
                  message: "详情保存成功!",
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
          }
        }
      });
    },
    //删除数据
    del(id) {
      this.$confirm("您确定要删除选中数据吗?", "提示", {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning",
      })
        .then(() => {
          this.$axios
            .post("role/del", {
              id: id,
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
.el-tabs {
  margin-top: -10px;
}
</style>
