<?php
/*
AdminController

Handles routing and rendering for all admin-related views.
Authentication and permission checks will be enforced by admin.php
once login functionality is implemented.
*/
?>
<?php
class AdminController
{
    public function dashboard()
    {
        require __DIR__ . '/../views/admin/dashboard.php';
    }
}
?>
