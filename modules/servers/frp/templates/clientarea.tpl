<div class="frp-dashboard">
    <div class="row">
        <!-- 状态概览 -->
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">服务概览</h3>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="metric">
                                <span class="title">状态</span>
                                <span class="value {if $service_status == 'active'}text-success{else}text-danger{/if}">
                                    {$service_status|ucfirst}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric">
                                <span class="title">带宽限制</span>
                                <span class="value">{$bandwidth_limit} Mbps</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric">
                                <span class="title">隧道数量</span>
                                <span class="value">{$tunnel_count}/{$max_tunnels}</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric">
                                <span class="title">今日流量</span>
                                <span class="value">{$today_traffic}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 隧道管理 -->
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">隧道管理</h3>
                    <div class="panel-actions">
                        <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addTunnelModal">
                            添加隧道
                        </button>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>名称</th>
                                    <th>类型</th>
                                    <th>本地端口</th>
                                    <th>远程端口/域名</th>
                                    <th>状态</th>
                                    <th>流量</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$tunnels item=tunnel}
                                <tr>
                                    <td>{$tunnel.name}</td>
                                    <td>{$tunnel.type}</td>
                                    <td>{$tunnel.local_port}</td>
                                    <td>
                                        {if $tunnel.type == 'http' || $tunnel.type == 'https'}
                                            {$tunnel.domain}
                                        {else}
                                            {$tunnel.remote_port}
                                        {/if}
                                    </td>
                                    <td>
                                        <span class="label label-{if $tunnel.status == 'online'}success{else}danger{/if}">
                                            {$tunnel.status}
                                        </span>
                                    </td>
                                    <td>{$tunnel.traffic}</td>
                                    <td>
                                        <button class="btn btn-primary btn-xs" onclick="editTunnel({$tunnel.id})">
                                            编辑
                                        </button>
                                        <button class="btn btn-danger btn-xs" onclick="deleteTunnel({$tunnel.id})">
                                            删除
                                        </button>
                                    </td>
                                </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 使用统计 -->
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">使用统计</h3>
                </div>
                <div class="panel-body">
                    <div id="trafficChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>

        <!-- 配置信息 -->
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">配置信息</h3>
                    <div class="panel-actions">
                        <button class="btn btn-primary btn-sm" onclick="downloadConfig()">
                            下载配置文件
                        </button>
                    </div>
                </div>
                <div class="panel-body">
                    <pre><code>{$config_content}</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 添加隧道模态框 -->
<div class="modal fade" id="addTunnelModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">添加隧道</h4>
            </div>
            <div class="modal-body">
                <form id="addTunnelForm">
                    <div class="form-group">
                        <label>名称</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>类型</label>
                        <select class="form-control" name="type" required>
                            <option value="tcp">TCP</option>
                            <option value="udp">UDP</option>
                            <option value="http">HTTP</option>
                            <option value="https">HTTPS</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>本地端口</label>
                        <input type="number" class="form-control" name="local_port" required>
                    </div>
                    <div class="form-group remote-port-group">
                        <label>远程端口</label>
                        <input type="number" class="form-control" name="remote_port">
                    </div>
                    <div class="form-group domain-group" style="display:none;">
                        <label>域名</label>
                        <input type="text" class="form-control" name="domain">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="submitTunnel()">添加</button>
            </div>
        </div>
    </div>
</div>

<style>
.frp-dashboard .metric {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 4px;
}

.frp-dashboard .metric .title {
    display: block;
    font-size: 14px;
    color: #666;
}

.frp-dashboard .metric .value {
    display: block;
    font-size: 24px;
    font-weight: bold;
    margin-top: 10px;
}

.panel-actions {
    float: right;
    margin-top: -20px;
}
</style>

<script>
// 图表初始化
function initChart() {
    var ctx = document.getElementById('trafficChart').getContext('2d');
    var chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: {$chart_labels|json_encode},
            datasets: [{
                label: '入站流量',
                data: {$chart_data.in|json_encode},
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }, {
                label: '出站流量',
                data: {$chart_data.out|json_encode},
                borderColor: 'rgb(255, 99, 132)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

// 表单处理
$('select[name="type"]').change(function() {
    var type = $(this).val();
    if (type === 'http' || type === 'https') {
        $('.remote-port-group').hide();
        $('.domain-group').show();
    } else {
        $('.remote-port-group').show();
        $('.domain-group').hide();
    }
});

function submitTunnel() {
    var formData = $('#addTunnelForm').serialize();
    $.post('clientarea.php?action=productdetails&id={$serviceid}&modop=custom&a=add_tunnel', 
        formData,
        function(response) {
            if (response.success) {
                $('#addTunnelModal').modal('hide');
                location.reload();
            } else {
                alert(response.message);
            }
        }
    );
}

function editTunnel(id) {
    // 实现编辑功能
}

function deleteTunnel(id) {
    if (confirm('确定要删除这个隧道吗？')) {
        $.post('clientarea.php?action=productdetails&id={$serviceid}&modop=custom&a=delete_tunnel', 
            {tunnel_id: id},
            function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message);
                }
            }
        );
    }
}

function downloadConfig() {
    window.location.href = 'clientarea.php?action=productdetails&id={$serviceid}&modop=custom&a=download_config';
}

// 初始化
$(document).ready(function() {
    initChart();
});
</script>
