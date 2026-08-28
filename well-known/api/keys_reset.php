<?php
require_once 'db_config.php';

// --- Global Variables ---
$reset_token = trim($_GET['token'] ?? '');
$user_key_to_reset = trim($_GET['key'] ?? '');
$user_data = null;
$api_response = null;

// --- HTML Helpers ---
function html_header($title = "Key Management Portal") {
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title}</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
body { background-color: #0d1117; font-family: 'Inter', sans-serif; color: #c9d1d9; }
.container { max-width: 1200px; }
.card { background-color: #161b22; border: 1px solid #30363d; }
th { background-color: #30363d; color: #ffffff; }
tr:nth-child(even) { background-color: #1a222c; }
tr:hover { background-color: #213243; }
.devices-bound { color: #f07f43; font-weight: bold; }
.devices-unbound { color: #238636; font-weight: bold; }
#toast-container { position: fixed; bottom: 20px; right: 20px; z-index: 1000; }
.toast { opacity: 0; transition: opacity 0.3s, transform 0.3s; transform: translateX(100%); }
.toast.show { opacity: 1; transform: translateX(0); }
</style>
</head>
<body>
<div class="container mx-auto p-4 pt-10">
HTML;
}

function html_footer($api_response_json = null) {
    $init_toast_script = $api_response_json ? "document.addEventListener('DOMContentLoaded',()=>{try{showToast(JSON.parse(`{$api_response_json}`).message, JSON.parse(`{$api_response_json}`).status);}catch(e){console.error(e);}});" : "";
    echo <<<HTML
</div>
<div id="toast-container"></div>
<script>
function showToast(message, type='success'){
    const container=document.getElementById('toast-container');
    const bgColor=type==='success'?'bg-green-600':'bg-red-600';
    const icon=type==='success'?'\\u2713':'\\u2717';
    const toast=document.createElement('div');
    toast.className=\`toast card p-4 mb-2 rounded-lg shadow-xl max-w-sm \${bgColor} text-white\`;
    toast.innerHTML=\`<div class="flex items-center"><span class="text-xl font-bold mr-3">\${icon}</span><span>\${message}</span></div>\`;
    container.appendChild(toast);
    setTimeout(()=>toast.classList.add('show'),10);
    setTimeout(()=>{toast.classList.remove('show');setTimeout(()=>container.removeChild(toast),300);},5000);
}

// AJAX reset
document.addEventListener('DOMContentLoaded',()=>{
    document.querySelectorAll('.reset-link').forEach(link=>{
        link.addEventListener('click',function(e){
            e.preventDefault();
            const key=this.getAttribute('data-key');
            const url=this.href;
            if(!confirm(\`Reset key \${key}?\`)) return;
            const originalText=this.textContent;
            this.textContent='Resetting...';
            this.classList.add('opacity-50','cursor-not-allowed');
            fetch(url,{method:'GET',headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'}})
            .then(res=>res.json())
            .then(data=>{
                this.textContent=originalText;
                this.classList.remove('opacity-50','cursor-not-allowed');
                showToast(data.message,data.status);
                if(data.status==='success'){
                    const row=this.closest('tr');
                    if(row){
                        const deviceCell=row.querySelector('[id^="device-cell-"]');
                        if(deviceCell) deviceCell.innerHTML='<span class="devices-unbound">UNBOUND</span><br>N/A';
                        row.classList.add('bg-green-900/30');
                        setTimeout(()=>row.classList.remove('bg-green-900/30'),2000);
                    }
                }
            }).catch(err=>{
                this.textContent=originalText;
                this.classList.remove('opacity-50','cursor-not-allowed');
                showToast('Network error occurred','error');
                console.error(err);
            });
        });
    });
});

{$init_toast_script}
</script>
</body>
</html>
HTML;
}

// --- DENIAL ---
function display_denied_access($conn,$msg){
    $expects_json=(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest');
    if($expects_json){header('Content-Type: application/json'); echo json_encode(['status'=>'error','message'=>$msg]); close_db_connection($conn); exit();}
    html_header('Access Denied');
    echo '<div class="card p-6 rounded-xl max-w-md mx-auto mt-10 text-center">';
    echo "<h2 class='text-2xl font-bold mb-4 text-red-500'>Access Denied</h2>";
    echo "<p class='text-lg'>".htmlspecialchars($msg)."</p>";
    echo '</div>';
    html_footer();
    close_db_connection($conn);
    exit();
}

// --- 1. TOKEN AUTH ---
if(empty($reset_token)) display_denied_access($conn,"Missing token parameter.");

$stmt=$conn->prepare("SELECT username, level FROM users WHERE reset_link_token=?");
if(!$stmt) display_denied_access($conn,"DB error: ".$conn->error);
$stmt->bind_param("s",$reset_token);
$stmt->execute();

// Use bind_result instead of get_result
$stmt->bind_result($username, $level);
if(!$stmt->fetch()){
    $stmt->close();
    display_denied_access($conn,"Invalid or expired reset token.");
}
$user_data=['username'=>$username,'level'=>$level];
$requester_username=$user_data['username'];
$requester_level=(int)$user_data['level'];
$stmt->close();

$expects_json=(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest');

// --- 2. RESET KEY ---
if(!empty($user_key_to_reset)){
    $stmt_key=$conn->prepare("SELECT id_keys, registrator, devices FROM keys_code WHERE user_key=?");
    if(!$stmt_key){
        $payload=['status'=>'error','message'=>'DB error: '.$conn->error];
        if($expects_json){header('Content-Type: application/json'); echo json_encode($payload); close_db_connection($conn); exit();}
        $api_response=$payload;
    }else{
        $stmt_key->bind_param("s",$user_key_to_reset);
        $stmt_key->execute();
        $stmt_key->bind_result($key_id, $key_registrator, $current_device);
        if($stmt_key->fetch()){
            $is_admin=$requester_level===1;
            $is_registrator=$requester_username===$key_registrator;
            if($is_admin||$is_registrator){
                if(is_null($current_device)){
                    $payload=['status'=>'error','message'=>"Key '{$user_key_to_reset}' already unbound."];
                }else{
                    $update_stmt=$conn->prepare("UPDATE keys_code SET devices=NULL, updated_at=NOW() WHERE user_key=?");
                    if(!$update_stmt){$payload=['status'=>'error','message'=>'DB error: '.$conn->error];}
                    else{
                        $update_stmt->bind_param("s",$user_key_to_reset);
                        if($update_stmt->execute()){
                            $info="KEY_RESET_API|Key: {$user_key_to_reset}|Registrator: {$key_registrator}|ResetBy: {$requester_username}|Auth: ".($is_admin?"Admin":"Registrator");
                            $history_stmt=$conn->prepare("INSERT INTO history (keys_id,user_do,info,created_at,updated_at) VALUES (?,?,?,NOW(),NOW())");
                            if($history_stmt){$history_stmt->bind_param("iss",$key_id,$requester_username,$info); $history_stmt->execute(); $history_stmt->close();}
                            $update_stmt->close();
                            $payload=['status'=>'success','message'=>"Key '{$user_key_to_reset}' successfully reset."];
                        }else{$payload=['status'=>'error','message'=>'DB error during reset: '.$conn->error];}
                    }
                }
            }else{$payload=['status'=>'error','message'=>'Authorization failed.'];}
        }else{$payload=['status'=>'error','message'=>"Key '{$user_key_to_reset}' not found."];}
        $stmt_key->close();
        if($expects_json){header('Content-Type: application/json'); echo json_encode($payload); close_db_connection($conn); exit();}
        $api_response=$payload;
    }
}

// --- 3. DISPLAY KEYS ---
html_header("Key Reset Viewer");
$user_role=$requester_level===1?"Admin (Level 1)":"Registrator (Level {$requester_level})";
$user_is_admin=$requester_level===1;

$sql="SELECT id_keys, game, user_key, duration, expired_date, max_devices, devices, registrator, created_at, updated_at FROM keys_code";
$params=[]; $types="";
if(!$user_is_admin){$sql.=" WHERE registrator=?"; $types="s"; $params[]=$requester_username;}
$sql.=" ORDER BY created_at DESC";

$stmt_view=$conn->prepare($sql);
if(!$stmt_view){echo '<div class="card p-6 rounded-lg mt-10 text-center"><p>DB error: '.htmlspecialchars($conn->error).'</p></div>'; html_footer(); close_db_connection($conn); exit();}
if(!empty($params)) $stmt_view->bind_param($types,...$params);
$stmt_view->execute();

// Use bind_result to fetch keys
$stmt_view->bind_result($id_keys, $game, $user_key, $duration, $expired_date, $max_devices, $devices, $registrator, $created_at, $updated_at);

$all_keys=[];
while($stmt_view->fetch()){
    $all_keys[]=[
        'id_keys'=>$id_keys,
        'game'=>$game,
        'user_key'=>$user_key,
        'duration'=>$duration,
        'expired_date'=>$expired_date,
        'max_devices'=>$max_devices,
        'devices'=>$devices,
        'registrator'=>$registrator,
        'created_at'=>$created_at,
        'updated_at'=>$updated_at
    ];
}
$stmt_view->close();

?>
<h1 class="text-3xl font-extrabold mb-2 text-white">Key Management Portal</h1>
<p class="text-gray-400 mb-6 border-b border-gray-700 pb-3">
Logged in as: <span class="font-semibold text-green-400"><?php echo htmlspecialchars($requester_username); ?></span> (<?php echo $user_role; ?>)
<?php if($user_is_admin) echo '<span class="text-yellow-500 ml-4">Showing ALL keys.</span>'; ?>
</p>
<?php
if(count($all_keys)>0){
    echo '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-700 rounded-lg overflow-hidden"><thead class="bg-gray-800"><tr>';
    echo '<th>Game</th><th>Key Code</th><th>Duration</th><th>Expired Date</th><th>Bound Device(s)</th><th>Registrator</th><th class="text-center">Action</th>';
    echo '</tr></thead><tbody class="divide-y divide-gray-700">';
    foreach($all_keys as $row){
        $key=htmlspecialchars($row['user_key']);
        $reset_url=htmlspecialchars($_SERVER['PHP_SELF']."?token=".urlencode($reset_token)."&key=".urlencode($key));
        $device_status=is_null($row['devices'])?'<span class="devices-unbound">UNBOUND</span>':'<span class="devices-bound">BOUND</span>';
        $device_info = is_null($row['devices'])
            ? 'N/A'
            : '<span class="text-xs break-all">' . htmlspecialchars($row['devices']) . '</span>';

        $displayKey = (strlen($key) > 2) ? 'xxxx' . substr($key, -2) : $key;
        echo "<tr id='row-{$key}'><td>".htmlspecialchars($row['game'])."</td><td class='font-mono text-sm'>{$displayKey}</td><td>".htmlspecialchars($row['duration'])." hrs</td><td class='text-xs'>".htmlspecialchars($row['expired_date'])."</td>";
        echo "<td id='device-cell-{$key}'>".$device_status."<br>".$device_info."</td><td>".htmlspecialchars($row['registrator'])."</td>";
        echo "<td class='text-center'><a href='{$reset_url}' data-key='{$key}' class='reset-link bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-1 px-3 rounded-full'>RESET NOW</a></td></tr>";
    }
    echo '</tbody></table></div>';
}else{echo "<div class='card p-6 rounded-lg mt-10 text-center'><p>No keys found.</p></div>";}

$api_response_json=null;
if(!empty($api_response)&&is_array($api_response)) $api_response_json=json_encode($api_response,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

html_footer($api_response_json);
close_db_connection($conn);
?>
