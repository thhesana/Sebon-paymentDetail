<?php
// Initialize variables
$search_vehicle = '';

// Database connection details
$serverName = "DESKTOP-IGPDORH";
$connectionOptions = array(
    "Database" => "SebonVehicleDetail",
    "Uid" => "sa",
    "PWD" => "Lazy-Car92"
);

// Establish SQL Server connection
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

// Get search_vehicle term from POST data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $search_vehicle = isset($_POST['search_vehicle']) ? htmlspecialchars(trim($_POST['search_vehicle'])) : '';
}

// Debugging: Print the search term
echo "<!-- Search term: $search_vehicle -->";

// Prepare SQL query with search functionality across multiple columns
$sql = "
    SELECT 
        vd.VehicleUser, 
        vd.VehicleType,
        vd.VehicleNumber, 
        pd.BlueBookRenewed, 
        FORMAT(
            CASE 
                WHEN DATEFROMPARTS(YEAR(GETDATE()), MONTH(pd.BlueBookRenewed), DAY(pd.BlueBookRenewed)) < GETDATE() 
                THEN DATEADD(YEAR, 1, DATEFROMPARTS(YEAR(GETDATE()), MONTH(pd.BlueBookRenewed), DAY(pd.BlueBookRenewed)))
                ELSE DATEFROMPARTS(YEAR(GETDATE()), MONTH(pd.BlueBookRenewed), DAY(pd.BlueBookRenewed))
            END, 'yyyy-MMM-dd'
        ) AS BlueBookRenew_date, 
        pd.PollutionTested, 
        FORMAT(
            CASE 
                WHEN DATEFROMPARTS(YEAR(GETDATE()), MONTH(pd.PollutionTested), DAY(pd.PollutionTested)) < GETDATE() 
                THEN DATEADD(YEAR, 1, DATEFROMPARTS(YEAR(GETDATE()), MONTH(pd.PollutionTested), DAY(pd.PollutionTested)))
                ELSE DATEFROMPARTS(YEAR(GETDATE()), MONTH(pd.PollutionTested), DAY(pd.PollutionTested))
            END, 'yyyy-MMM-dd'
        ) AS PollutionTestedRenew_date, 
        pd.Insured, 
        FORMAT(
            CASE 
                WHEN DATEFROMPARTS(YEAR(GETDATE()), MONTH(pd.Insured), DAY(pd.Insured)) < GETDATE() 
                THEN DATEADD(YEAR, 1, DATEFROMPARTS(YEAR(GETDATE()), MONTH(pd.Insured), DAY(pd.Insured)))
                ELSE DATEFROMPARTS(YEAR(GETDATE()), MONTH(pd.Insured), DAY(pd.Insured))
            END, 'yyyy-MMM-dd'
        ) AS InsuranceRenew_date
    FROM 
        dbo.VehiclePaidDetail AS pd 
    INNER JOIN 
        dbo.VehicleDetail AS vd 
        ON pd.VehicleDetail_ID = vd.VehicleDetail_ID
    WHERE 
        vd.VehicleUser LIKE ? OR
        vd.VehicleType LIKE ? OR
        vd.VehicleNumber LIKE ? OR
        pd.BlueBookRenewed LIKE ?
";

// Parameters for the query
$params = array(
    "%$search_vehicle%", 
    "%$search_vehicle%", 
    "%$search_vehicle%", 
    "%$search_vehicle%"
);

// Execute the query with parameters
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}

// Debugging: Check if there are rows
if (sqlsrv_has_rows($stmt)) {
    echo "<!-- Rows found -->";
} else {
    echo "<!-- No rows found -->";
}

?>

<h2><center>SEBON VEHICLE DETAIL</center></h2>
<form id="searchForm" method="POST" class="search-form">
    <input type="text" name="search_vehicle" placeholder="Search vehicle.." value="<?php echo htmlspecialchars($search_vehicle); ?>" />
    <input type="submit" value="Search Vehicle" />
</form>

<div id="vehicleResults">
    <?php if ($stmt && sqlsrv_has_rows($stmt)): ?>
        <table>
            <tr>
                <th>Vehicle User</th>
                <th>Vehicle Type</th>
                <th>Vehicle Number</th>
                <th>Blue Book Renew Date</th>
                <th>Pollution Tested Renew Date</th>
                <th>Insurance Renew Date</th>
            </tr>
            <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['VehicleUser']); ?></td>
                    <td><?php echo htmlspecialchars($row['VehicleType']); ?></td>
                    <td><?php echo htmlspecialchars($row['VehicleNumber']); ?></td>
                    <td><?php echo htmlspecialchars($row['BlueBookRenew_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['PollutionTestedRenew_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['InsuranceRenew_date']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No results found.</p>
    <?php endif; ?>
</div>

<?php
// Close the connection
if ($conn) {
    sqlsrv_close($conn);
}
?>

<style>
    .search-form {
        text-align: right; /* Aligns form content to the right */
        margin: 20px; /* Adds margin for spacing */
    }
    .search-form input[type="text"] {
        margin-right: 10px; /* Adds space between the input and button */
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    table, th, td {
        border: 1px solid black;
    }
    th, td {
        padding: 8px 12px;
        text-align: center;
    }
</style>
