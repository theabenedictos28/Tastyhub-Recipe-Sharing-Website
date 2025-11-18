<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$categories = isset($_POST['category']) && is_array($_POST['category']) ? $_POST['category'] : [];
$ingredients = isset($_POST['ingredients']) && is_array($_POST['ingredients']) ? $_POST['ingredients'] : [];
$tags = isset($_POST['tags']) && is_array($_POST['tags']) ? $_POST['tags'] : [];
$difflevel = isset($_POST['difflevel']) ? $_POST['difflevel'] : '';
$preparation_types = isset($_POST['preparation_type']) && is_array($_POST['preparation_type']) ? $_POST['preparation_type'] : [];
$cooking_time = isset($_POST['recipe_cooktime']) ? trim($_POST['recipe_cooktime']) : "";
$budget_level = isset($_POST['budget_level']) ? trim($_POST['budget_level']) : "";
$equipment = isset($_POST['equipment']) && is_array($_POST['equipment']) ? $_POST['equipment'] : [];


// DELETE previous category preferences
$deletePref = $conn->prepare("DELETE FROM user_category WHERE user_id = ?");
if ($deletePref) {
    $deletePref->bind_param("i", $user_id);
    $deletePref->execute();
    $deletePref->close();
} else {
    die("Delete user_category failed: " . $conn->error);
}

// INSERT new category preferences
if (!empty($categories)) {
    $stmtCat = $conn->prepare("INSERT INTO user_category (user_id, category) VALUES (?, ?)");
    if (!$stmtCat) {
        die("Insert user_category failed: " . $conn->error);
    }

    foreach ($categories as $category) {
        $stmtCat->bind_param("is", $user_id, $category);
        $stmtCat->execute();
    }
    $stmtCat->close();
}

// DELETE previous ingredient preferences
$deleteIng = $conn->prepare("DELETE FROM user_ingredients WHERE user_id = ?");
if ($deleteIng) {
    $deleteIng->bind_param("i", $user_id);
    $deleteIng->execute();
    $deleteIng->close();
} else {
    die("Delete user_ingredients failed: " . $conn->error);
}

// INSERT new ingredient preferences
if (!empty($ingredients)) {
    $stmtIng = $conn->prepare("INSERT INTO user_ingredients (user_id, ingredient) VALUES (?, ?)");
    if (!$stmtIng) {
        die("Insert user_ingredients failed: " . $conn->error);
    }

    foreach ($ingredients as $ingredient) {
        $stmtIng->bind_param("is", $user_id, $ingredient);
        $stmtIng->execute();
    }
    $stmtIng->close();
}

// DELETE previous tag preferences
$deleteTags = $conn->prepare("DELETE FROM user_tags WHERE user_id = ?");
if ($deleteTags) {
    $deleteTags->bind_param("i", $user_id);
    $deleteTags->execute();
    $deleteTags->close();
} else {
    die("Delete user_tags failed: " . $conn->error);
}
// INSERT new tag preferences
if (!empty($tags)) {
    $stmtTag = $conn->prepare("INSERT INTO user_tags (user_id, tag) VALUES (?, ?)");
    if (!$stmtTag) {
        die("Insert user_tags failed: " . $conn->error);
    }

    foreach ($tags as $tag) {
        $stmtTag->bind_param("is", $user_id, $tag);
        $stmtTag->execute();
    }
    $stmtTag->close();
}

// DELETE previous difficulty preferences
$deleteDiff = $conn->prepare("DELETE FROM user_difflevel WHERE user_id = ?");
if ($deleteDiff) {
    $deleteDiff->bind_param("i", $user_id);
    $deleteDiff->execute();
    $deleteDiff->close();
} else {
    die("Delete user_difflevel failed: " . $conn->error);
}

// INSERT new difficulty preferences
if (!empty($difflevel)) {
    $stmtDiff = $conn->prepare("INSERT INTO user_difflevel (user_id, diff_level) VALUES (?, ?)");
    if (!$stmtDiff) {
        die("Insert user_difflevel failed: " . $conn->error);
    }
        $stmtDiff->bind_param("is", $user_id, $difflevel);
        $stmtDiff->execute();
        $stmtDiff->close();
}

// DELETE previous preparation type preferences
$deletePrep = $conn->prepare("DELETE FROM user_preparation WHERE user_id = ?");
if ($deletePrep) {
    $deletePrep->bind_param("i", $user_id);
    $deletePrep->execute();
    $deletePrep->close();
} else {
    die("Delete user_preparation failed: " . $conn->error);
}

// INSERT new preparation type preferences
if (!empty($preparation_types)) {
    $stmtPrep = $conn->prepare("INSERT INTO user_preparation (user_id, preparation_type) VALUES (?, ?)");
    if (!$stmtPrep) {
        die("Insert user_preparation failed: " . $conn->error);
    }

    foreach ($preparation_types as $prep) {
        $stmtPrep->bind_param("is", $user_id, $prep);
        $stmtPrep->execute();
    }
    $stmtPrep->close();
}

// DELETE previous cooking time preference
$deleteCook = $conn->prepare("DELETE FROM user_cooktime WHERE user_id = ?");
if ($deleteCook) {
    $deleteCook->bind_param("i", $user_id);
    $deleteCook->execute();
    $deleteCook->close();
} else {
    die("Delete user_cooktime failed: " . $conn->error);
}

// INSERT new cooking time preference
if (!empty($cooking_time)) {
    $stmtCook = $conn->prepare("INSERT INTO user_cooktime (user_id, cook_time) VALUES (?, ?)");
    if (!$stmtCook) {
        die("Insert user_cooktime failed: " . $conn->error);
    }

    $stmtCook->bind_param("is", $user_id, $cooking_time);
    $stmtCook->execute();
    $stmtCook->close();
}

// DELETE previous budget level preference
$deleteBudget = $conn->prepare("DELETE FROM user_budgetlevel WHERE user_id = ?");
if ($deleteBudget) {
    $deleteBudget->bind_param("i", $user_id);
    $deleteBudget->execute();
    $deleteBudget->close();
} else {
    die("Delete user_budgetlevel failed: " . $conn->error);
}

// INSERT new budget level preference
if (!empty($budget_level)) {
    $stmtBudget = $conn->prepare("INSERT INTO user_budgetlevel (user_id, budget_level) VALUES (?, ?)");
    if (!$stmtBudget) {
        die("Insert user_budgetlevel failed: " . $conn->error);
    }
    $stmtBudget->bind_param("is", $user_id, $budget_level);
    $stmtBudget->execute();
    $stmtBudget->close();
}

// DELETE previous equipment preferences
$deleteEquip = $conn->prepare("DELETE FROM user_equipment WHERE user_id = ?");
if ($deleteEquip) {
    $deleteEquip->bind_param("i", $user_id);
    $deleteEquip->execute();
    $deleteEquip->close();
} else {
    die("Delete user_equipment failed: " . $conn->error);
}

// INSERT new equipment preferences
if (!empty($equipment)) {
    $stmtEquip = $conn->prepare("INSERT INTO user_equipment (user_id, equipment_name) VALUES (?, ?)");
    if (!$stmtEquip) {
        die("Insert user_equipment failed: " . $conn->error);
    }

    foreach ($equipment as $equip) {
        $stmtEquip->bind_param("is", $user_id, $equip);
        $stmtEquip->execute();
    }
    $stmtEquip->close();
}


$conn->close();
header("Location: dashboard.php");
exit;
?>
