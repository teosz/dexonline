function beginEdit(id){
  $('#displayDate').datepicker({ dateFormat: 'yy-mm-dd' });
  $('#lexicon').autocomplete("wotdGetDefinitions.php").result(function(event, item){
    var lexicon = item[0].replace(/^([^ | ^\-]*) -.*$/, '$1');
    var definitionId = item[0].replace(/^[^\[|\[^\{]*\[\{([0-9]+)\}\]$/, '$1');
    $('#definitionId').val(definitionId);
    $('#lexicon').val(lexicon);
  });
  $('#priority')[0].style.width = '400px';
  $('#displayDate')[0].style.width = '400px';
  $('#lexicon')[0].style.width = '400px';
  if ($('#definitionId').length == 0){
    $('#lexicon').after('<input type="hidden" id="definitionId"/>');
  }
}

function beforeSubmit(data){
  data.definitionId = $('#definitionId').val();
  var sel_id = $("#wotdGrid").jqGrid('getGridParam','selrow');
  var rowData = $('#wotdGrid').jqGrid('getRowData', sel_id);
  data.oldDefinitionId = rowData.definitionId;
  return [true];
}

function initGrid(){
  jQuery().ready(function (){
    $('#wotdGrid').jqGrid({
      url: 'wotdTableRows.php',
      datatype: 'xml',
      colNames: ['Source name', 'Lexicon', 'Defition HTML', 'User\'s name', 'Display data', 'Priority', 'WotD type', 'Definition ID'],
      colModel: [
        {name: 'source', index: 'source', width: '120'},
        {name: 'lexicon', index: 'lexicon', width: '120', editable: true},
        {name: 'htmlRep', index: 'htmlRep', width: '120'},
        {name: 'name', index: 'u.name', width: '120'},
        {name: 'displayDate', index: 'displayDate', width: '120', editable: true},
        {name: 'priority', index: 'priority', width: '120', editable: true},
        {name: 'refType', index: 'refType', width: '120', editable: true, edittype: 'select', editoptions: {value: 'Definition:Definition'}}, 
        {name: 'definitionId', index: 'definitionId', width: '120', editable: false, hidden: true}
      ],
      rowNum: 25,
      //autoWidth: true,
      width: 1200,
      rowList: [25, 50, 75],
      sortName: 'displayDate',
      pager: $('#wotdPaging'), 
      viewRecords: true,
      sortOrder: 'desc',
      caption: 'Word of the Day',
      editurl: 'wotdSave.php'
    });
    $('#wotdGrid').navGrid('#wotdPaging', {},
      {
        reloadAfterSubmit: true,
        width: 600,
        beforeSubmit: function(data){
          return beforeSubmit(data);
        },
        afterShowForm: function(id){
          beginEdit(id);
        }
      },
      {
        reloadAfterSubmit: true,
        width: 600,
        beforeSubmit: function(data){
          return beforeSubmit(data);
        },
        afterShowForm: function(id){
          beginEdit(id);
        }
      }
    );
  });
}