<!-- -------------------------------------------------- -->
<!-- File: app/views/accounts/edit.php                  -->
<!-- This is the form for editing an account's details. -->
<!-- -------------------------------------------------- -->
<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 max-w-lg mx-auto">
    <h2 class="text-2xl font-bold mb-6">Edit Account</h2>
    <form action="/account/edit/<?php echo $account['account_id']; ?>" method="POST">
        <div class="mb-4">
            <label for="account_name" class="block mb-2 text-sm font-medium text-gray-300">Account Name</label>
            <input type="text" name="account_name" id="account_name" class="form-input" value="<?php echo htmlspecialchars($account['account_name']); ?>" required>
        </div>
        <div class="mb-6">
            <label for="account_type" class="block mb-2 text-sm font-medium text-gray-300">Account Type</label>
            <select name="account_type" id="account_type" class="form-input">
                <option value="Checking" <?php if ($account['account_type'] === 'Checking') echo 'selected'; ?>>Checking / Current</option>
                <option value="Savings" <?php if ($account['account_type'] === 'Savings') echo 'selected'; ?>>Savings</option>
                <option value="Credit Card" <?php if ($account['account_type'] === 'Credit Card') echo 'selected'; ?>>Credit Card</option>
                <option value="Cash" <?php if ($account['account_type'] === 'Cash') echo 'selected'; ?>>Cash</option>
            </select>
        </div>
        <button type="submit" class="w-full btn btn-primary">Update Account</button>
    </form>
</section>
