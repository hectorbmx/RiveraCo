param([string]$MariaDbBin = 'C:\xampp\mysql\bin')
$ErrorActionPreference = 'Stop'
$taskRoot = Split-Path $PSScriptRoot -Parent
$taskToken = [guid]::NewGuid().ToString('N')
$taskBase = Join-Path $taskRoot 'storage\app\testing'
$taskFixture = Join-Path $taskBase ('mobile-order-' + $taskToken)
$taskData = Join-Path $taskFixture 'data'
$taskServer = $null
$taskExit = 1
New-Item -ItemType Directory -Path $taskFixture -Force | Out-Null
$taskListener = [Net.Sockets.TcpListener]::new([Net.IPAddress]::Loopback, 0)
$taskListener.Start()
$taskPort = $taskListener.LocalEndpoint.Port
$taskListener.Stop()
$taskConfig = Join-Path $taskFixture 'fixture.json'
$taskSettings = @{ token=$taskToken; port=$taskPort; datadir=$taskData; database=('rivera_mobile_order_test_'+$taskToken.Substring(0,12)) }
[IO.File]::WriteAllText($taskConfig, ($taskSettings | ConvertTo-Json), [Text.UTF8Encoding]::new($false))
try {
    & (Join-Path $MariaDbBin 'mysql_install_db.exe') "--datadir=$taskData" "--port=$taskPort" --silent
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo inicializar la instancia temporal.' }
    $taskArguments = @('--no-defaults', ('--basedir='+ (Split-Path $MariaDbBin -Parent)), "--datadir=$taskData", "--port=$taskPort",
        '--bind-address=127.0.0.1', '--skip-name-resolve', '--skip-log-bin', '--innodb-buffer-pool-size=32M', '--max-connections=12', '--console')
    $taskServer = Start-Process -FilePath (Join-Path $MariaDbBin 'mysqld.exe') -ArgumentList $taskArguments -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $taskFixture 'server.out.log') -RedirectStandardError (Join-Path $taskFixture 'server.err.log')
    & php (Join-Path $PSScriptRoot 'test_mobile_order_concurrency.php') $taskConfig
    $taskExit = $LASTEXITCODE
} finally {
    if ($null -ne $taskServer -and !$taskServer.HasExited) {
        # También comprobar @@datadir antes de apagar: el puerto podría haber sido ocupado por otro proceso.
        & php (Join-Path $PSScriptRoot 'test_mobile_order_concurrency.php') $taskConfig --shutdown
        if ($LASTEXITCODE -ne 0) { throw 'No se confirmó la identidad del servidor para apagarlo; se conservan sus datos.' }
        $taskServer.WaitForExit(10000) | Out-Null
        if (!$taskServer.HasExited) { throw ('La instancia temporal sigue activa: PID '+$taskServer.Id+'. No se retiran sus datos.') }
    }
    # Solo retirar datos de esta instancia; conservar informe/logs para diagnóstico.
    if (Test-Path -LiteralPath $taskData) {
        $taskResolved = [IO.Path]::GetFullPath($taskData)
        $taskAllowed = [IO.Path]::GetFullPath($taskBase).TrimEnd('\')+'\'
        if (!$taskResolved.StartsWith($taskAllowed) -or (Split-Path (Split-Path $taskResolved -Parent) -Leaf) -ne ('mobile-order-'+$taskToken)) { throw 'Directorio temporal fuera de alcance.' }
        if ((Get-Item -LiteralPath $taskResolved -Force).Attributes -band [IO.FileAttributes]::ReparsePoint) { throw 'No se retira un enlace.' }
        Remove-Item -LiteralPath $taskResolved -Recurse -Force
    }
    Write-Output ('FIXTURE='+$taskFixture)
}
exit $taskExit
