<?php


if (!defined('ABSPATH')) {
    exit;
}

class DMR_Submissions
{

    public function __construct()
    {
        add_action('admin_post_dmr_delete_submission', array($this, 'delete_submission'));
        add_action('admin_post_dmr_update_status', array($this, 'update_status'));
    }

    public function render_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Check for single view
        if (isset($_GET['view']) && !empty($_GET['view'])) {
            $this->render_single_view(intval($_GET['view']));
            return;
        }

        $this->render_list_view();
    }

    private function render_list_view()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dmr_self_checks';

        // Pagination
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;

        // Search and filters
        $where = array('1=1');
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        if (!empty($search)) {
            $where[] = $wpdb->prepare('(full_name LIKE %s OR email LIKE %s)', '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');
        }

        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        if (!empty($status_filter)) {
            $where[] = $wpdb->prepare('status = %s', $status_filter);
        }

        $category_filter = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
        if (!empty($category_filter)) {
            $where[] = $wpdb->prepare('category = %s', $category_filter);
        }

        $where_sql = implode(' AND ', $where);

        // Get total count
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE $where_sql");
        $total_pages = ceil($total_items / $per_page);

        // Get submissions
        $submissions = $wpdb->get_results(
            "SELECT * FROM $table_name WHERE $where_sql ORDER BY created_at DESC LIMIT $per_page OFFSET $offset"
        );

        ?>
                <div class="wrap">
                    <h1>Submissions</h1>
            
                    <form method="get" class="dmr-filters">
                        <input type="hidden" name="page" value="dmr-submissions">
                
                        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search by name or email">
                
                        <select name="status">
                            <option value="">All Statuses</option>
                            <option value="submitted" <?php selected($status_filter, 'submitted'); ?>>Submitted</option>
                            <option value="contacted" <?php selected($status_filter, 'contacted'); ?>>Contacted</option>
                            <option value="archived" <?php selected($status_filter, 'archived'); ?>>Archived</option>
                        </select>
                
                        <select name="category">
                            <option value="">All Categories</option>
                            <option value="low" <?php selected($category_filter, 'low'); ?>>Low Stress</option>
                            <option value="moderate" <?php selected($category_filter, 'moderate'); ?>>Moderate Stress</option>
                            <option value="high" <?php selected($category_filter, 'high'); ?>>High Stress</option>
                        </select>
                
                        <button type="submit" class="button">Filter</button>
                        <?php if (!empty($search) || !empty($status_filter) || !empty($category_filter)): ?>
                                <a href="<?php echo admin_url('admin.php?page=dmr-submissions'); ?>" class="button">Clear</a>
                        <?php endif; ?>
                    </form>
            
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Score</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($submissions)): ?>
                                    <tr>
                                        <td colspan="7">No submissions found.</td>
                                    </tr>
                            <?php else: ?>
                                    <?php foreach ($submissions as $submission): ?>
                                            <tr>
                                                <td><strong><?php echo esc_html($submission->full_name); ?></strong></td>
                                                <td><?php echo esc_html($submission->email); ?></td>
                                                <td><?php echo esc_html($submission->score); ?>/40</td>
                                                <td>
                                                    <span class="dmr-badge dmr-category-<?php echo esc_attr($submission->category); ?>">
                                                        <?php echo esc_html(ucfirst($submission->category)); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="dmr-status-<?php echo esc_attr($submission->status); ?>">
                                                        <?php echo esc_html(ucfirst($submission->status)); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo esc_html(date('M j, Y g:i A', strtotime($submission->created_at))); ?></td>
                                                <td>
                                                    <a href="<?php echo admin_url('admin.php?page=dmr-submissions&view=' . $submission->id); ?>" class="button button-small">View</a>
                                                </td>
                                            </tr>
                                    <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
            
                    <?php if ($total_pages > 1): ?>
                            <div class="tablenav">
                                <div class="tablenav-pages">
                                    <?php
                                    echo paginate_links(array(
                                        'base' => add_query_arg('paged', '%#%'),
                                        'format' => '',
                                        'current' => $current_page,
                                        'total' => $total_pages,
                                        'prev_text' => '&laquo;',
                                        'next_text' => '&raquo;'
                                    ));
                                    ?>
                                </div>
                            </div>
                    <?php endif; ?>
                </div>
                <?php
    }

    private function render_single_view($id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dmr_self_checks';

        $submission = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

        if (!$submission) {
            echo '<div class="wrap"><p>Submission not found.</p></div>';
            return;
        }

        $answers = json_decode($submission->quiz_answers, true);
        $config = get_option('dmr_self_check_config', array());
        $questions = $config['questions'] ?? array();

        ?>
                <div class="wrap">
                    <h1>Submission Details</h1>
                    <a href="<?php echo admin_url('admin.php?page=dmr-submissions'); ?>" class="button">&larr; Back to List</a>
            
                    <div class="dmr-submission-detail">
                        <div class="dmr-detail-section">
                            <h2>Contact Information</h2>
                            <table class="form-table">
                                <tr>
                                    <th>Full Name:</th>
                                    <td><?php echo esc_html($submission->full_name); ?></td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td><a href="mailto:<?php echo esc_attr($submission->email); ?>"><?php echo esc_html($submission->email); ?></a></td>
                                </tr>
                                <?php if (!empty($submission->phone)): ?>
                                        <tr>
                                            <th>Phone:</th>
                                            <td><?php echo esc_html($submission->phone); ?></td>
                                        </tr>
                                <?php endif; ?>
                                <?php if (!empty($submission->notes)): ?>
                                        <tr>
                                            <th>Notes:</th>
                                            <td><?php echo nl2br(esc_html($submission->notes)); ?></td>
                                        </tr>
                                <?php endif; ?>
                                <tr>
                                    <th>Submitted:</th>
                                    <td><?php echo esc_html(date('F j, Y g:i A', strtotime($submission->created_at))); ?></td>
                                </tr>
                            </table>
                        </div>
                
                        <div class="dmr-detail-section">
                            <h2>Results</h2>
                            <table class="form-table">
                                <tr>
                                    <th>Total Score:</th>
                                    <td><strong><?php echo esc_html($submission->score); ?></strong> / 40</td>
                                </tr>
                                <tr>
                                    <th>Category:</th>
                                    <td>
                                        <span class="dmr-badge dmr-category-<?php echo esc_attr($submission->category); ?>">
                                            <?php echo esc_html(ucfirst($submission->category)); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Recommendation:</th>
                                    <td><?php echo esc_html($submission->recommendation); ?></td>
                                </tr>
                            </table>
                        </div>
                
                        <div class="dmr-detail-section">
                            <h2>Quiz Answers</h2>
                            <table class="widefat">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Question</th>
                                        <th>Answer</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($answers as $index => $value): ?>
                                            <tr>
                                                <td><?php echo ($index + 1); ?></td>
                                                <td><?php echo esc_html($questions[$index] ?? 'Question ' . ($index + 1)); ?></td>
                                                <td><?php echo esc_html($value); ?></td>
                                            </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                
                        <div class="dmr-detail-section">
                            <h2>Status Management</h2>
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                                <?php wp_nonce_field('dmr_update_status', 'dmr_status_nonce'); ?>
                                <input type="hidden" name="action" value="dmr_update_status">
                                <input type="hidden" name="submission_id" value="<?php echo $submission->id; ?>">
                        
                                <select name="status">
                                    <option value="submitted" <?php selected($submission->status, 'submitted'); ?>>Submitted</option>
                                    <option value="contacted" <?php selected($submission->status, 'contacted'); ?>>Contacted</option>
                                    <option value="archived" <?php selected($submission->status, 'archived'); ?>>Archived</option>
                                </select>
                        
                                <button type="submit" class="button button-primary">Update Status</button>
                            </form>
                        </div>
                
                        <div class="dmr-detail-section">
                            <h2>Delete Submission</h2>
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" onsubmit="return confirm('Are you sure you want to delete this submission? This action cannot be undone.');">
                                <?php wp_nonce_field('dmr_delete_submission', 'dmr_delete_nonce'); ?>
                                <input type="hidden" name="action" value="dmr_delete_submission">
                                <input type="hidden" name="submission_id" value="<?php echo $submission->id; ?>">
                                <button type="submit" class="button button-link-delete">Delete Submission</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php
    }

    public function delete_submission()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('dmr_delete_submission', 'dmr_delete_nonce');

        $submission_id = intval($_POST['submission_id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'dmr_self_checks';

        $wpdb->delete($table_name, array('id' => $submission_id), array('%d'));

        wp_redirect(admin_url('admin.php?page=dmr-submissions&deleted=1'));
        exit;
    }

    public function update_status()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('dmr_update_status', 'dmr_status_nonce');

        $submission_id = intval($_POST['submission_id']);
        $status = sanitize_text_field($_POST['status']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'dmr_self_checks';

        $wpdb->update(
            $table_name,
            array('status' => $status, 'updated_at' => current_time('mysql')),
            array('id' => $submission_id),
            array('%s', '%s'),
            array('%d')
        );

        wp_redirect(admin_url('admin.php?page=dmr-submissions&view=' . $submission_id . '&updated=1'));
        exit;
    }
}