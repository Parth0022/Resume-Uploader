<?php
/*
Plugin Name: Contact Form with resume uploader
Description: Integrates SMTP from Microsoft Outlook with a contact form with resume uploader using PHPMailer.
Version: 5.1
Author: Partha Santosh
*/

session_start();

require_once ABSPATH . WPINC . '/class-phpmailer.php';
require_once ABSPATH . WPINC . '/class-smtp.php';

add_shortcode('contact_form', 'contact_form_function');
add_action('admin_menu', 'outlook_smtp_integration_menu');

function outlook_smtp_integration_menu()
{
    add_menu_page('Outlook SMTP Integration', 'Outlook SMTP', 'manage_options', 'outlook-smtp-integration', 'outlook_smtp_integration_page');
}

function outlook_smtp_integration_page()
{
    ?>
    <div class="wrap">
        <h2>Outlook SMTP Integration Settings</h2>
        <form method="post">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Outlook Email Address</th>
                    <td><input type="text" name="outlook_email"
                            value="<?php echo isset($_POST['outlook_email']) ? $_POST['outlook_email'] : ''; ?>" required>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Outlook Password</th>
                    <td><input type="password" name="outlook_password"
                            value="<?php echo isset($_POST['outlook_password']) ? $_POST['outlook_password'] : ''; ?>"
                            required></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary"
                    value="Save Changes"></p>
        </form>
    </div>
    <?php

    if (isset($_POST['submit'])) {
        $outlook_email = isset($_POST['outlook_email']) ? $_POST['outlook_email'] : '';
        $outlook_password = isset($_POST['outlook_password']) ? $_POST['outlook_password'] : '';

        $smtp_details = new stdClass();
        $smtp_details->email = $outlook_email;
        $smtp_details->pass = $outlook_password;
        $_SESSION['smtp_details'] = $smtp_details;
    }
}

function contact_form_function()
{
    ?>
    <style>
        .outlook-contact-form {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0px 0px 10px 0px rgba(0, 0, 0, 0.1);
        }

        .outlook-contact-form h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .outlook-contact-form input[type="text"],
        .outlook-contact-form input[type="email"],
        .outlook-contact-form textarea,
        .outlook-contact-form input[type="file"],
        .outlook-contact-form input[type="submit"] {
            width: 100%;
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .outlook-contact-form input[type="submit"] {
            background-color: #0073e6;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }
    </style>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
    <div class="outlook-contact-form">
        <h2>Join Us</h2>
        <input type="hidden" name="action" value="process_contact_form">
        <input type="text" name="name" placeholder="Your Name" required>
        <input type="email" name="email" placeholder="Your Email" required>
        <textarea name="message" placeholder="Your Message" required></textarea>
        <input type="file" name="resume" accept=".pdf,.doc,.docx">
        <input type="submit" name="submit" value="Submit">
    </div>
    </form>
    <?php
}

add_action('admin_post_process_contact_form', 'process_contact_form_data');

function process_contact_form_data()
{
    if (isset($_POST['submit'])) {
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $message = sanitize_textarea_field($_POST['message']);

        if (isset($_SESSION['smtp_details'])) {
            $smtp_details = $_SESSION['smtp_details'];
            $Email = $smtp_details->email;
            $Password = $smtp_details->pass;

            $outlook_email = $Email;
            $outlook_password = $Password;
            $outlook_host = 'smtp.office365.com';
            $outlook_port = 587;

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $outlook_host;
            $mail->Port = $outlook_port;
            $mail->SMTPAuth = true;
            $mail->Username = $outlook_email;
            $mail->Password = $outlook_password;
            $mail->SMTPSecure = 'tls';

            $resume = $_FILES['resume'];
            $resume_tmp_name = $resume['tmp_name'];
            $resume_name = $resume['name'];

            $mail->setFrom($outlook_email, 'Company Name');
            $mail->addAddress($Email);
            $mail->Subject = $name;
            $mail->Body = 'Name: ' . $name . PHP_EOL .
                          'Email: ' . $email . PHP_EOL .
                          'Message: ' . $message . PHP_EOL;

            // Attach resume if uploaded
            if (!empty($resume_tmp_name)) {
                $mail->addAttachment($resume_tmp_name, $resume_name);
            }

            try {
                $mail->send();
                echo '<div class="updated"><p>Thank you for reaching out to us we will get back to you soon</p></div>';
            } catch (Exception $e) {
                echo '<div class="error"><p>Error: ' . $mail->ErrorInfo . '</p></div>';
            }
        } else {
            echo "SMTP details not set";
        }
    }
}
?>
