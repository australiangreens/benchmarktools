{literal}
<style>
.divTable{
	display: table;
	width: 100%;
}
.divTableRow {
	display: table-row;
}
.divTableCell, .divTableHead {
	border: 1px solid #999999;
	display: table-cell;
	padding: 3px 10px;
}
.divTableHeading {
  padding: 3px 10px;
  border: 1px solid #999999;
	background-color: #EEE;
	display: table-cell;
	font-weight: bold;
}
.divTableFoot {
	background-color: #EEE;
	display: table-footer-group;
	font-weight: bold;
}
.divTableBody {
	display: table-row-group;
}
</style>
{/literal}
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    cj('.divTable a#delete').on('click', function(e) {
      deleteFile(cj(this));
      e.preventDefault();
    });
    function deleteFile($el) {
      // Get the filename
      var fileName = $el.data('filename');
      var validExt=fileName.split('.').pop();
      // Just a cross-check
      if (validExt === 'csv') {
        CRM.confirm({
          message: '{/literal}{ts escape='js'}Are you sure you want to delete this file?{/ts}{literal}'
        }).on('crmConfirm:yes', function() {
          var postUrl = {/literal}"{crmURL p='civicrm/admin/benchmark/results' h=0 q='action=delete&filename='}"{literal};
          var request = $.post(postUrl + fileName);
          CRM.status({/literal}"{ts escape='js'}File Deleted{/ts}"{literal}, request);
          request.done(function() {
            CRM.refreshParent('.divTable');
          });
        })
      }

    }

  });
</script>
{/literal}
<h3>Benchmark results</h3>
{if $emptyLog == 1}
  <h4>-- No benchmark results found -- </h4>
{else}
  <div class="divTable">
    <div class="divTableBody">
      <div class="divTableRow">
        <div class="divTableHeading">Filename</div>
        <div class="divTableHeading">Date</div>
        <div class="divTableHeading">Actions</div>
      </div>
      {foreach from=$csv_opts item=csvfile}
        <div class="divTableRow">
          <div class="divTableCell">{$csvfile.basename}</div>
          <div class="divTableCell">{$csvfile.timestamp}</div>
          <div class="divTableCell">
            <a id="view" href="/civicrm/admin/benchmark/results?action=view&filename={$csvfile.basename}" title="Download File">View/Download</a> -
            <a id="delete" data-filename="{$csvfile.basename}" href="action=delete&filename={$csvfile.basename}" title="Delete File">Delete</a>
          </div>
        </div>
      {/foreach}
    </div>
  </div>

{/if}