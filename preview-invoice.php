<?php
include 'cookie-check.php';
include "db.php";

// Get the invoice ID from the URL
if (isset($_GET['invoice_id'])) {
    $invoice_id = $_GET['invoice_id'];

    // Fetch the invoice details
    $invoice_stmt = $pdo->prepare("SELECT i.*, c.cust_name, c.cust_email_id, c.cust_mob_no 
                                   FROM invoices i
                                   JOIN customer_mst c ON i.customer_id = c.cust_id
                                   WHERE i.invoice_id = ?");
    $invoice_stmt->execute([$invoice_id]);
    $invoice = $invoice_stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch the invoice items
    $items_stmt = $pdo->prepare("SELECT ii.*, p.product_name 
                                 FROM invoice_items ii
                                 JOIN product_mst p ON ii.product_id = p.product_id
                                 WHERE ii.invoice_id = ?");
    $items_stmt->execute([$invoice_id]);
    $invoice_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    die("Invalid invoice ID.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Preview</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            font-size: 16px;
            line-height: 24px;
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #555;
        }
        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
        }
        .invoice-box table td {
            padding: 5px;
            vertical-align: top;
        }
        .invoice-box table tr td:nth-child(2) {
            text-align: right;
        }
        .invoice-box table tr.top table td {
            padding-bottom: 20px;
        }
        .invoice-box table tr.heading td {
            background: #eee;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }
        .invoice-box table tr.item td {
            border-bottom: 1px solid #eee;
        }
        .invoice-box table tr.total td:nth-child(2) {
            border-top: 2px solid #eee;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="invoice-box">
    <table cellpadding="0" cellspacing="0">
        <tr class="top">
            <td colspan="2">
                <table>
                    <tr>
                        <td class="title">
                            <h2>Invoice</h2>
                        </td>
                        <td>
                            Invoice #: <?php echo $invoice['invoice_number']; ?><br>
                            Created: <?php echo $invoice['invoice_date']; ?><br>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr class="information">
            <td colspan="2">
                <table>
                    <tr>
                        <td>
                            Customer Name: <?php echo $invoice['cust_name']; ?><br>
                            Email: <?php echo $invoice['cust_email_id']; ?><br>
                            Phone: <?php echo $invoice['cust_mob_no']; ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr class="heading">
            <td>Product</td>
            <td>Amount</td>
        </tr>

        <?php foreach ($invoice_items as $item): ?>
            <tr class="item">
                <td><?php echo $item['product_name']; ?> (x<?php echo $item['quantity']; ?> @ <?php echo $item['rate']; ?>)</td>
                <td><?php echo $item['amount']; ?></td>
            </tr>
        <?php endforeach; ?>

        <tr class="total">
            <td></td>
            <td>Total: <?php echo $invoice['net_amount']; ?></td>
        </tr>
    </table>
    <div class="mt-4">
        <button class="btn btn-primary" onclick="window.print();">Print Invoice</button>
    </div>
</div>

</body>
</html>
