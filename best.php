<?php
session_start();

// Initialize data file if it doesn't exist
$dataFile = 'products.json';
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([]));
}

// Function to read data
function readProducts() {
    global $dataFile;
    $json = file_get_contents($dataFile);
    return json_decode($json, true) ?: [];
}

// Function to write data
function writeProducts($data) {
    global $dataFile;
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
}

// Handle form submissions
$message = '';
$editMode = false;
$editProduct = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $products = readProducts();
    
    // CREATE
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $newProduct = [
            'id' => uniqid(),
            'name' => htmlspecialchars($_POST['name']),
            'brand' => htmlspecialchars($_POST['brand']),
            'model' => htmlspecialchars($_POST['model']),
            'price' => floatval($_POST['price']),
            'stock' => intval($_POST['stock']),
            'color' => htmlspecialchars($_POST['color']),
            'storage' => intval($_POST['storage']),
            'release_year' => intval($_POST['release_year']),
            'created_at' => date('Y-m-d H:i:s')
        ];
        $products[] = $newProduct;
        writeProducts($products);
        $message = 'Phone added successfully!';
    }
    
    // UPDATE
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $id = $_POST['id'];
        foreach ($products as $key => $product) {
            if ($product['id'] === $id) {
                $products[$key]['name'] = htmlspecialchars($_POST['name']);
                $products[$key]['brand'] = htmlspecialchars($_POST['brand']);
                $products[$key]['model'] = htmlspecialchars($_POST['model']);
                $products[$key]['price'] = floatval($_POST['price']);
                $products[$key]['stock'] = intval($_POST['stock']);
                $products[$key]['color'] = htmlspecialchars($_POST['color']);
                $products[$key]['storage'] = intval($_POST['storage']);
                $products[$key]['release_year'] = intval($_POST['release_year']);
                break;
            }
        }
        writeProducts($products);
        $message = 'Phone updated successfully!';
    }
    
    // DELETE
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $products = array_filter($products, function($product) use ($id) {
            return $product['id'] !== $id;
        });
        $products = array_values($products); // Re-index array
        writeProducts($products);
        $message = 'Phone deleted successfully!';
    }
}

// Handle edit request
if (isset($_GET['edit'])) {
    $editMode = true;
    $products = readProducts();
    foreach ($products as $product) {
        if ($product['id'] === $_GET['edit']) {
            $editProduct = $product;
            break;
        }
    }
}

$products = readProducts();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📱 Phone Store Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 30px;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2.5em;
        }

        h2 {
            color: #555;
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        .message {
            background: #4caf50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-container {
            background: #f9f9f9;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 2px solid #e0e0e0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .button-group {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .table-container {
            margin-top: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        thead {
            background: #667eea;
            color: white;
        }

        th, td {
            padding: 15px;
            text-align: left;
        }

        th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
        }

        tbody tr {
            border-bottom: 1px solid #e0e0e0;
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: #f5f5f5;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .btn-edit, .btn-delete {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-edit {
            background: #ffc107;
            color: #333;
        }

        .btn-edit:hover {
            background: #ffb300;
            transform: scale(1.05);
        }

        .btn-delete {
            background: #f44336;
            color: white;
        }

        .btn-delete:hover {
            background: #d32f2f;
            transform: scale(1.05);
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 18px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 10px;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📱 Phone Store Management System</h1>
        
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- CREATE / UPDATE FORM -->
        <div class="form-container">
            <h2><?php echo $editMode ? '✏️ Edit Phone' : '➕ Add New Phone'; ?></h2>
            <form method="POST" action="index.php">
                <input type="hidden" name="action" value="<?php echo $editMode ? 'update' : 'create'; ?>">
                <?php if ($editMode): ?>
                    <input type="hidden" name="id" value="<?php echo $editProduct['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="name">Phone Name:</label>
                    <input type="text" id="name" name="name" 
                           value="<?php echo $editMode ? $editProduct['name'] : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="brand">Brand:</label>
                    <input type="text" id="brand" name="brand" 
                           value="<?php echo $editMode ? $editProduct['brand'] : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="model">Model:</label>
                    <input type="text" id="model" name="model" 
                           value="<?php echo $editMode ? $editProduct['model'] : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="price">Price ($):</label>
                    <input type="number" id="price" name="price" step="0.01" 
                           value="<?php echo $editMode ? $editProduct['price'] : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="stock">Stock Quantity:</label>
                    <input type="number" id="stock" name="stock" 
                           value="<?php echo $editMode ? $editProduct['stock'] : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="color">Color:</label>
                    <input type="text" id="color" name="color" 
                           value="<?php echo $editMode ? $editProduct['color'] : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="storage">Storage (GB):</label>
                    <input type="number" id="storage" name="storage" 
                           value="<?php echo $editMode ? $editProduct['storage'] : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="release_year">Release Year:</label>
                    <input type="number" id="release_year" name="release_year" 
                           value="<?php echo $editMode ? $editProduct['release_year'] : ''; ?>" 
                           required>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editMode ? 'Update' : 'Add'; ?> Phone
                    </button>
                    <?php if ($editMode): ?>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- READ / DISPLAY DATA -->
        <div class="table-container">
            <h2>📊 All Phones (<?php echo count($products); ?>)</h2>
            
            <?php if (empty($products)): ?>
                <p class="no-data">No phones found. Add your first phone product!</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Model</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Color</th>
                            <th>Storage</th>
                            <th>Release Year</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['brand']; ?></td>
                                <td><?php echo $product['model']; ?></td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo $product['stock']; ?></td>
                                <td><?php echo $product['color']; ?></td>
                                <td><?php echo $product['storage']; ?>GB</td>
                                <td><?php echo $product['release_year']; ?></td>
                                <td><?php echo $product['created_at']; ?></td>
                                <td class="actions">
                                    <a href="?edit=<?php echo $product['id']; ?>" class="btn-edit">Edit</a>
                                    <form method="POST" style="display:inline;" 
                                          onsubmit="return confirm('Are you sure you want to delete this phone?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="btn-delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>