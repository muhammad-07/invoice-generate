<?php
session_start();
include 'cookie-check.php';
include 'db.php'; // Include the database connection

// Fetch customers
$stmt_customers = $pdo->query("SELECT cust_id, cust_name FROM customer_mst ORDER BY cust_name ASC");
$customers = $stmt_customers->fetchAll();

// Fetch products
$stmt_products = $pdo->query("SELECT product_id, product_name, product_stock FROM product_mst ORDER BY product_name ASC");
$products = $stmt_products->fetchAll();

$alert = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the invoice data from the form
    $invoice_number = $_POST['invoice_number'];
    $invoice_date = $_POST['invoice_date'];
    $customer_id = $_POST['customer_id'];
    $net_amount = $_POST['net_amount'];
    $remarks = $_POST['remarks'];

    // Get the product data from the form (arrays)
    $products = $_POST['product'];
    $quantities = $_POST['quantity'];
    $rates = $_POST['rate'];
    // $invoice_id = $_POST['invoice_number'];

    // Start a transaction to ensure atomicity
    $pdo->beginTransaction();

    try {
        // Insert into the invoices table
        $invoice_stmt = $pdo->prepare("INSERT INTO invoices (invoice_number, invoice_date, customer_id, net_amount, remarks) 
                                        VALUES (?, ?, ?, ?, ?)");
        $invoice_stmt->execute([$invoice_number, $invoice_date, $customer_id, $net_amount, $remarks]);


        $invoice_id = $pdo->lastInsertId();

        // Insert each product into the invoice_items table
        $item_stmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, product_id, quantity, rate, amount) 
                                    VALUES (?, ?, ?, ?, ?)");

        // Loop through the products and insert each item
        foreach ($products as $index => $product_id) {
            $quantity = $quantities[$index];
            $rate = $rates[$index];
            $amount = $quantity * $rate;

            $item_stmt->execute([$invoice_id, $product_id, $quantity, $rate, $amount]);
        }

        // Commit the transaction
        $pdo->commit();

        header("Location: preview-invoice.php?invoice_id=" . $invoice_id);
        exit();
        // $msg = "Invoice generated successfully with Invoice ID: " . $invoice_id;
        // $alert = '<div class="alert alert-primary" role="alert">'.$msg.'</div>';
    } catch (Exception $e) {
        // Rollback transaction if an error occurs
        $pdo->rollBack();
        die("Failed to generate invoice: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Form</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.9.2/umd/popper.min.js"
        integrity="sha512-2rNj2KJ+D8s1ceNasTIex6z4HWyOnEYLVC3FigGOmyQCZc2eBXKgOxQmo3oKLHyfcj53uz4QMsRCWNbLd32Q1g=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

</head>

<body>

    <div class="container mt-5">
        <h2>Create Invoice</h2>
        <?= $alert ?>
        <form method="POST" id="invoiceForm" novalidate>
            <div class="row">

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="customer">Select Customer</label>
                        <select name="customer_id" id="customer" class="form-control" required>
                            <option value="">Choose customer...</option>
                            <?php
                            foreach ($customers as $customer) {
                                echo "<option value='" . $customer['cust_id'] . "'>" . $customer['cust_name'] . "</option>";
                            }
                            ?>
                        </select>
                        <div class="invalid-feedback">Please select a customer.</div>
                    </div>
                </div>


                <!-- Invoice Date -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="invoice_date">Invoice Date</label>
                        <input type="date" name="invoice_date" id="invoice_date" class="form-control" required>
                        <div class="invalid-feedback">Please select an invoice date.</div>
                    </div>
                </div>

                <!-- Invoice Number (auto-generated) -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="invoice_number">Invoice Number</label>
                        <input type="text" name="invoice_number" id="invoice_number" class="form-control"
                            value="<?php echo 'INV-' . time(); ?>" readonly>
                        <div class="invalid-feedback">Invoice number is required.</div>
                    </div>
                </div>


            </div>

            <!-- Product rows dynamically added with "Add More Products" button -->
            <div id="productList">
                <div class="form-row product-row">
                    <div class="form-group col-md-3">
                        <label for="product">Product</label>
                        <select name="product[]" class="form-control product-input" required>
                            <option value="">Select Product</option>
                            <?php
                            foreach ($products as $product) {
                                echo "<option value='" . $product['product_id'] . "'>" . $product['product_name'] . " (Stock: " . $product['product_stock'] . ")</option>";
                            }
                            ?>
                        </select>
                        <div class="invalid-feedback">Please select a product.</div>
                    </div>

                    <div class="form-group col-md-2">
                        <label for="quantity">Quantity</label>
                        <input type="number" name="quantity[]" class="form-control quantity-input" data-toggle="tooltip"
                            title="Enter quantity" required>
                        <div class="invalid-feedback">Enter a valid quantity.</div>
                    </div>

                    <div class="form-group col-md-2">
                        <label for="rate">Rate</label>
                        <input type="number" name="rate[]" class="form-control" required>
                        <div class="invalid-feedback">Enter a valid rate.</div>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="description">Description</label>
                        <input type="text" name="description[]" class="form-control">
                    </div>

                    <!-- Remove button -->
                    <div class="form-group col-md-1">
                        <button type="button" class="btn btn-danger remove-product-row mt-4">Remove</button>
                    </div>
                </div>
            </div>

            <!-- Add More Products button -->
            <button type="button" class="btn btn-secondary" data-toggle="tooltip" data-placement="top"
                title="Enter quantity" id="addMoreProducts">Add More Products</button>

            <!-- Net Amount -->
            <div class="form-group mt-4">
                <label for="net_amount">Net Amount</label>
                <input type="number" name="net_amount" id="net_amount" class="form-control" required readonly>
                <div class="invalid-feedback">Net amount is required.</div>
            </div>

            <!-- Remarks -->
            <div class="form-group">
                <label for="remarks">Remarks</label>
                <textarea name="remarks" id="remarks" class="form-control" rows="3"></textarea>
            </div>

            <!-- Submit button -->
            <button type="submit" class="btn btn-primary mt-3">Generate Invoice</button>
        </form>
    </div>

    <script>
        // Cloning the product row
        $(document).ready(function () {
            $('[data-toggle="tooltip"]').tooltip();
            // Click event to add new product row
            $('#addMoreProducts').on('click', function () {
                // Clone the first product row
                var clonedRow = $('.product-row:first').clone();

                // Clear values of cloned row
                clonedRow.find('input').val('');  // Clear text inputs
                clonedRow.find('select').val(''); // Clear select inputs

                clonedRow.find('select').removeClass('is-invalid');
                clonedRow.find('input').removeClass('is-invalid');

                // Append the cloned row after the last product row
                $('#productList').append(clonedRow);
            });

            // Click event to remove a product row
            $(document).on('click', '.remove-product-row', function () {
                // Only remove the row if there is more than one product row
                if ($('.product-row').length > 1) {
                    $(this).closest('.product-row').remove();
                } else {
                    alert('You must have at least one product.');
                }
            });

            // Automatically calculate the net amount based on products and quantities
            $(document).on('input', 'input[name="quantity[]"], input[name="rate[]"]', function () {
                calculateNetAmount();
            });

            function calculateNetAmount() {
                var netAmount = 0;
                $('#productList .product-row').each(function () {
                    var quantity = $(this).find('input[name="quantity[]"]').val();
                    var rate = $(this).find('input[name="rate[]"]').val();
                    if (quantity && rate) {
                        netAmount += (quantity * rate);
                    }
                });
                $('#net_amount').val(netAmount);
            }

            $('#invoiceForm').on('submit', function (event) {
                event.preventDefault(); // Prevent form submission
                var form = this;

                if (!validateProductSelection() || stockValid == false) {
                    event.stopPropagation();
                    alert('Please resolve the errors.');
                }

                // Check validity of the form
                if (form.checkValidity() === false) {
                    event.stopPropagation();
                } else {
                    // Submit form if validation passes
                    form.submit();
                }

                $(form).addClass('was-validated');
            });





            // STOCK VALIDATION
            $(document).on('input', '.quantity-input', function () {
                thisinput = $(this);
                // var rowId = $(this).attr('id').split('_')[1]; // Get the row id from the input field id
                var productId = $(this).closest(".form-row").find(".product-input").val();  // Get the selected product ID
                var quantity = $(this).val();  // Get the entered quantity

                validateStock(thisinput, productId, quantity);
            });


            $(document).on('change', '.product-input', function () {
                validateProductSelection();

                thisinput = $(this);
                // var rowId = $(this).attr('id').split('_')[1]; // Get the row id from the input field id
                var quantity = $(this).closest(".form-row").find(".quantity-input").val();  // Get the selected product ID
                var productId = $(this).val();  // Get the entered quantity

                validateStock(thisinput, productId, quantity);
            });
            var stockValid = false;
            function validateStock(thisinput, productId, quantity) {
thisinput.removeClass('is-invalid');
                if (productId && quantity) {
                    $.ajax({
                        url: 'ajax/check_stock.php', // PHP script for validation
                        type: 'POST',
                        data: {
                            product_id: productId,
                            quantity: quantity
                        },
                        success: function (response) {
                            var result = JSON.parse(response);
                            if (!result.valid) {
                                thisinput.addClass('is-invalid');
                                thisinput.siblings('.invalid-feedback').text(result.message);
                                // alert(result.message);
                            } else {
                                thisinput.removeClass('is-invalid');
                                stockValid = true;
                            }
                        }
                    });
                }
                return stockValid;
            }

            function validateProductSelection() {
                // Create an array to store selected product IDs
                var selectedProducts = [];
                var duplicateFound = false;

                // Loop through each product select dropdown
                $('.product-input').each(function () {
                    var productValue = $(this).val();
                    $(this).removeClass('is-invalid');
                    if (productValue) {
                        // Check if the product is already selected in another dropdown
                        if (selectedProducts.includes(productValue)) {
                            console.log('Duplicate found: ' + productValue);
                            $(this).addClass('is-invalid');
                            $(this).siblings('.invalid-feedback').text('This product is already selected');
                            $(this).prop('selectedIndex', 0);
                            duplicateFound = true;
                        } else {
                            // Remove error class if no duplicates
                            $(this).removeClass('is-invalid');
                            $(this).siblings('.invalid-feedback').text('');
                            // Add product to the selected array
                            selectedProducts.push(productValue);
                        }
                    }
                });

                return !duplicateFound;
            }



        });



    </script>

</body>

</html>