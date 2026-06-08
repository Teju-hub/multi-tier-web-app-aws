<?php
require_once('config.php');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql = "SELECT * FROM data";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>TechWave - Web Application</title>
</head>
<body>
    <h1>TechWave Website</h1>
    <h2>Data from RDS MySQL Database</h2>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <table border="1">
            <tr>
                <?php
                $fields = mysqli_fetch_fields($result);
                foreach ($fields as $field) {
                    echo "<th>" . $field->name . "</th>";
                }
                ?>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <?php foreach ($row as $value): ?>
                    <td><?php echo $value; ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No data found in table.</p>
    <?php endif; ?>

    <?php
    $host = gethostname();
    echo "<br><p><strong>Server Hostname:</strong> " . $host . "</p>";
    mysqli_close($conn);
    ?>
</body>
</html>