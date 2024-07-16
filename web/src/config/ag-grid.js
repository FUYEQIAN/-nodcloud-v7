import Vue from "vue";
import "ag-grid-community/dist/styles/ag-grid.css";
import "ag-grid-community/dist/styles/ag-theme-balham.css";
const grid = {
	//获取数据表格数据|ag-grid
	getGridData(grid) {
		let rowData = [];
		grid.api.forEachNode((node) => {
			rowData.push(node.data);
		});
		return rowData;
	},
	//添加数据表格预设数据|ag-grid
	addGridRow(options) {
		//删除预设空行
		options.api.forEachNode((node) => {
			node.data.key == null && (options.api.applyTransaction({
				remove: [node.data]
			}))
		});
		//添加预设数据
		options.api.applyTransaction({
			add: [options.context.row]
		});
	},
	//添加数据表格数据|ag-grid
	pushGridData(options, arr) {
		//数据插入表格
		let rowNode = options.api.applyTransaction({
			add: arr
		});
		//添加预设数据
		this.addGridRow(options);
		return rowNode;
	},
	//更新数据表格数据|ag-grid
	updateGridData(options,key,field,val) {
		let rowNode=options.api.getRowNode(key);
		rowNode.setDataValue(field,val);
	},
	//最后一行指定字段|激活编辑
	startEditingLastCell(options,key){
		setTimeout(()=>{
			options.api.startEditingCell({
				colKey:key,
				rowIndex:options.api.getModel().getRowCount()-1
			});
		},200);
	},
	//数据表格TABLE事件
	gridTabToNextCell(params) {
		let api = params.previousCellPosition.column.gridApi;
		let rowColId = params.previousCellPosition.column.colId;
		let rowIndex = params.previousCellPosition.rowIndex;
		let columns = api.columnController.allDisplayedColumns;
		let nextColumn=null;
		for (var i = 0; i < columns.length; i++) {
			if(columns[i].colId==rowColId){
				let record = columns.slice(i+1);
				if(record.length!=0){
					for (let item of record) {
						if(item.colDef.hasOwnProperty('editable') && item.colDef.editable){
							nextColumn=item;
							break;
						}
					}
				}
			}
		}
		let nextIndex=rowIndex;
		if(nextColumn==null){
			let rowCount=api.getModel().getRowCount();
			if(rowIndex==rowCount-1){
				nextColumn=params.previousCellPosition.column;
			}else{
				nextIndex=rowIndex+1;
				nextColumn=columns.find(column=>column.colDef.hasOwnProperty('editable') && column.colDef.editable);
			}
		}
		return {
			rowIndex: nextIndex,
			column: nextColumn
		};
	}
}
Vue.prototype.$grid = grid;
