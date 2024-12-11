<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">操作日志</h3>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>时间</th>
                        <th>操作</th>
                        <th>描述</th>
                        <th>IP地址</th>
                        <th>状态</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$logs item=log}
                    <tr class="{if $log.status == 'error'}danger{/if}">
                        <td>{$log.created_at}</td>
                        <td>{$log.action}</td>
                        <td>{$log.description}</td>
                        <td>{$log.ip_address}</td>
                        <td>
                            <span class="label label-{if $log.status == 'success'}success{else}danger{/if}">
                                {$log.status}
                            </span>
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.panel-actions {
    float: right;
    margin-top: -20px;
}

.label {
    display: inline-block;
    min-width: 60px;
    text-align: center;
}
</style>
