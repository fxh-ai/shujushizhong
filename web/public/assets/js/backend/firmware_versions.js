define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'firmware_versions/index' + location.search,
                    add_url: 'firmware_versions/add',
                    edit_url: 'firmware_versions/edit',
                    del_url: 'firmware_versions/del',
                    multi_url: 'firmware_versions/multi',
                    import_url: 'firmware_versions/import',
                    table: 'firmware_versions',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'version', title: __('Version'), operate: 'LIKE'},
                        {field: 'file_path', title: __('File_path'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'file_size', title: __('File_size')},
                        {field: 'download_url', title: __('Download_url'), operate: 'LIKE', formatter: Table.api.formatter.url},
                        {field: 'force_update', title: __('Force_update')},
                        {field: 'status', title: __('Status')},
                        {field: 'created_at', title: __('Created_at'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
