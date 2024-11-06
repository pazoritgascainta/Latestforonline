<?php
session_name('user_session'); 
session_start();


if (!isset($_SESSION['homeowner_id'])) {
    header("Location: login.php");
    exit;
}

$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$homeowner_id = $_SESSION['homeowner_id'];


$sql_total_balance = "SELECT SUM(total_amount) as total_balance FROM billing WHERE homeowner_id = ?";
$stmt_total_balance = $conn->prepare($sql_total_balance);
$stmt_total_balance->bind_param("i", $homeowner_id);
$stmt_total_balance->execute();
$result_total_balance = $stmt_total_balance->get_result();
$row_total_balance = $result_total_balance->fetch_assoc();


$total_balance = isset($row_total_balance['total_balance']) ? $row_total_balance['total_balance'] : 0;

// Query to fetch all the billing data for the homeowner
$sql_billing = "SELECT billing_date, due_date, monthly_due, status, total_amount FROM billing WHERE homeowner_id = ?";
$stmt_billing = $conn->prepare($sql_billing);
$stmt_billing->bind_param("i", $homeowner_id);
$stmt_billing->execute();
$result_billing = $stmt_billing->get_result();

// Query to fetch accepted appointments for the homeowner
$sql_accepted_appointments = "SELECT date, amount, status, purpose, amenity_id FROM accepted_appointments WHERE homeowner_id = ?";

$stmt_accepted_appointments = $conn->prepare($sql_accepted_appointments);
$stmt_accepted_appointments->bind_param("i", $homeowner_id);
$stmt_accepted_appointments->execute();
$result_accepted_appointments = $stmt_accepted_appointments->get_result();


$total_appointments_amount = 0;


while ($row = $result_accepted_appointments->fetch_assoc()) {
    $total_appointments_amount += $row['amount'];
}



$result_accepted_appointments->data_seek(0); 


$amenity_names = [
    1 => 'Clubhouse Court',
    2 => 'Townhouse Court',
    3 => 'Clubhouse Swimming Pool',
    4 => 'Townhouse Swimming Pool',
    5 => 'Consultation',
    6 => 'Bluehouse Court'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard</title>
    <link rel="stylesheet" href="payment.css">
</head>
<body>
    <?php include 'usersidebar.php'; ?>

    <div class="main-content">
        <div class="container">
            <header>
                <div class="date-section">
                    <h1>Current Date: <span id="current-date"></span></h1>
                </div>
                <div class="balance-section">
                    <div class="balance">
                        <span>Total Balance</span>
                        <h2>₱<?php echo number_format($total_balance + $total_appointments_amount, 2); ?></h2>
                    </div>
                    <div class="modues">
                        <span>Monthly Dues</span>
                        <h2>₱<?php echo number_format($total_balance, 2); ?></h2>
                    </div>
                    <div class="appointments">
                        <span>Other Fees</span>
                        <h2>₱<?php echo number_format($total_appointments_amount, 2); ?></h2>
                    </div>
                </div>
            </header>
            <section class="proof-of-payment">
    <h3>View Payment History</h3>
    <a href="payment_history_user.php" id="payment-history-link">Payment History</a>
</section>
            <section class="combined-schedule">
                <h2>Payments</h2>
                <table>
                <thead>
                        <tr>                    
                        <tr>
                            <th>Billing Date</th>
                            <th>Due Date</th>
                            <th>Monthly Due</th>
                            <th>Status</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                       
                        while ($row = $result_billing->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td data-label='Billing Date'>" . htmlspecialchars($row['billing_date']) . "</td>";
                            echo "<td data-label='Due Date'>" . htmlspecialchars($row['due_date']) . "</td>";
                            echo "<td data-label='Monthly Due'>₱" . number_format($row['monthly_due'], 2) . "</td>";
                            echo "<td data-label='Status'>" . ucfirst($row['status']) . "</td>";
                            echo "<td data-label='Total Amount'>₱" . number_format($row['total_amount'], 2) . "</td>";
                            echo "</tr>";
                        }
                        ?>    
                    </tbody>
                    <thead>
                        <tr>
                            <th colspan="3">Other Fees (Appointments)</th>
                            <th colspan="2"></th>
                        </tr>
                        <tr>
                            <th>Date</th>
                            <th>Amenity</th>
                            <th>Purpose</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                      
                        $appointments_by_date = [];
                        $grand_total_amount = 0; 

                        while ($row = $result_accepted_appointments->fetch_assoc()) {
                            $date = $row['date'];

                           
                            if (!isset($appointments_by_date[$date])) {
                                $appointments_by_date[$date] = [
                                    'amount' => 0,
                                    'status' => ucfirst($row['status']),
                                    'purpose' => $row['purpose'], 
                                    'amenity_id' => $row['amenity_id'] 
                                ];
                            }

                            
                            $appointments_by_date[$date]['amount'] += $row['amount'];
                        }

                       
                        foreach ($appointments_by_date as $date => $data) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($date) . "</td>";
                            $amenity_id = $data['amenity_id'];
                            $amenity_name = isset($amenity_names[$amenity_id]) ? $amenity_names[$amenity_id] : 'Unknown Amenity';
                            
                            echo "<td>" . htmlspecialchars($amenity_name) . "</td>";
                            echo "<td>" . htmlspecialchars($data['purpose']) . "</td>"; 
                            echo "<td>₱" . number_format($data['amount'], 2) . "</td>"; 
                            echo "<td>" . $data['status'] . "</td>"; 
                         
                            echo "</tr>";

                           
                            $grand_total_amount += $data['amount'];
                        }
                        ?>
                        
                        <tr>
                            <td><strong>Total</strong></td>
                            <td><strong>₱<?php echo number_format($grand_total_amount, 2); ?></strong></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </section>
            <section class="proof-of-payment">
    <h3>Pay Here</h3>
    <a href="payment_page.php" target="_blank" id="pay-here-link">Proceed to Payment</a>
</section>


<section class="proof-of-payment">
    <h3>View Billing Statement</h3>
    <a href="BillingStatement.php" id="billing-link" onclick="openBillingStatement(event)">Billing Statement for the Month of: </a>
</section>


            <section class="proof-of-payment">
    <h3>Proof of Payment</h3>
    <form method="POST" enctype="multipart/form-data" action="upload.php" id="upload-form">
    <input type="file" id="upload-file" name="upload-file" accept="image/*" required>
        <input type="hidden" name="homeowner_id" value="<?php echo htmlspecialchars($homeowner_id); ?>">
        <input type="hidden" name="billing_reference" id="billingReference">
        <button type="submit" id="upload-button">Upload</button>
    </form>
    <div id="loader" class="loader" style="display: none;"></div> 
</section>

        </div>
    </div>

    <script src="payment.js"></script>
    <script>
   document.getElementById('upload-form').addEventListener('submit', function(event) {
    event.preventDefault(); 

    const fileInput = document.getElementById('upload-file');
    const file = fileInput.files[0];
    const loader = document.getElementById('loader');
    const uploadButton = document.getElementById('upload-button');

 
    if (file && file.type.startsWith('image/')) {
      
        loader.style.display = 'block';
        uploadButton.disabled = true;

     
        const formData = new FormData();
        formData.append('upload-file', file);
        formData.append('homeowner_id', document.querySelector('input[name="homeowner_id"]').value);
        formData.append('billing_reference', document.getElementById('billingReference').value);

    
        fetch('upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text()) 
        .then(data => {
            alert("Upload successful!"); 
            loader.style.display = 'none';
            uploadButton.disabled = false; 
            console.log(data); 
        })
        .catch(error => {
            alert("There was an error uploading the file.");
            loader.style.display = 'none'; 
            uploadButton.disabled = false; 
            console.error('Error:', error);
        });
    } else {
        alert("Invalid file type. Please upload an image file.");
    }
});

</script>
<script>
function openBillingStatement(event) {
    event.preventDefault();
    window.open('BillingStatement.php', '_blank');
}
</script>


</body>
</html>


