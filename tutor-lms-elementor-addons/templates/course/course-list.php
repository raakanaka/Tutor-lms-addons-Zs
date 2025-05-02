<div class="etlms-course-list-container">
    <!-- Sidebar Filter (Desktop & Mobile Toggle) -->
    <div id="course-filter-sidebar" class="course-filter-sidebar">
        <h6>Pilih Sesuai</h6>
        <form method="get" id="course-filter-form">
    <!-- Kategori -->
    <div class="filter-group">
        <label for="course_category">Kategori:</label>
        <select name="course_category" id="course_category">
            <option value="">Semua Kategori</option>
            <?php
            $categories = get_terms('course-category');
            foreach ($categories as $cat) {
                $selected = (isset($_GET['course_category']) && $_GET['course_category'] == $cat->term_id) ? 'selected' : '';
                echo "<option value='{$cat->term_id}' {$selected}>{$cat->name}</option>";
            }
            ?>
        </select>
    </div>

    <!-- Level -->
<div class="filter-group">
    <label for="course_level">Level:</label>
    <select name="course_level" id="course_level">
        <option value="">Semua Level</option>
<option value="beginner" <?php selected($_GET['course_level'] ?? '', 'beginner'); ?>>Beginner</option>
<option value="intermediate" <?php selected($_GET['course_level'] ?? '', 'intermediate'); ?>>Intermediate</option>
<option value="expert" <?php selected($_GET['course_level'] ?? '', 'expert'); ?>>Expert</option>
    </select>
</div>



    <!-- Tahun -->
    <div class="filter-group">
        <label for="course_year">Tahun:</label>
        <select name="course_year" id="course_year">
            <option value="">Semua Tahun</option>
            <?php
            $years = range(date('Y'), 2018); // Ganti dengan range sesuai kebutuhan
            foreach ($years as $year) {
                $selected = (isset($_GET['course_year']) && $_GET['course_year'] == $year) ? 'selected' : '';
                echo "<option value='{$year}' {$selected}>{$year}</option>";
            }
            ?>
        </select>
    </div>

    <button type="submit" class="tutor-button">Terapkan Filter</button>
</form>

        <?php do_action('my_custom_course_filters'); ?>
    </div>

    <!-- Floating Filter Button (Mobile only) -->
    <button id="toggle-course-filter" class="mobile-filter-button">
        <span>Filter</span>
    </button>

    <!-- Course Listing Area -->
    <div class="etlms-course-list-content <?php tutor_container_classes(); ?> etlms-course-list-main-wrap">
        <?php
        $course_list_perpage     = $settings['course_list_perpage'];
        $course_list_column      = $settings['course_list_column'];
        $include_by_categories   = $settings['course_list_include_by_categories'];
        $exclude_by_categories   = $settings['course_list_exclude_by_categories'];
        $include_by_authors      = $settings['course_list_include_by_authors'];
        $exclude_by_authors      = $settings['course_list_exclude_by_authors'];
        $order_by                = $settings['course_list_order_by'];
        $order                   = $settings['course_list_order'];
        $paged = isset($_GET['current_page']) ? sanitize_text_field($_GET['current_page']) : 1;

        if (!function_exists('is_bundle_enabled')) {
            function is_bundle_enabled() {
                $basename = plugin_basename(TUTOR_COURSE_BUNDLE_FILE);
                return tutor_utils()->is_addon_enabled($basename);
            }
        }

        $listing_postype = (in_array('tutor-pro/tutor-pro.php', apply_filters('active_plugins', get_option('active_plugins'))) && is_bundle_enabled()) 
            ? ['courses', 'course-bundle'] 
            : tutor()->course_post_type;

        $args = array(
            'post_type'      => $listing_postype,
            'post_status'    => 'publish',
            'posts_per_page' => $course_list_perpage,
            'paged'          => $paged,
            'tax_query'      => array('relation' => 'AND'),
        );

        if (!empty($include_by_categories)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'course-category',
                'field'    => 'term_id',
                'terms'    => $include_by_categories,
                'operator' => 'IN',
            );
        }

        if (!empty($exclude_by_categories)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'course-category',
                'field'    => 'term_id',
                'terms'    => $exclude_by_categories,
                'operator' => 'NOT IN',
            );
        }

        if (!empty($include_by_authors)) {
            $args['author__in'] = $include_by_authors;
        }

        if (!empty($exclude_by_authors)) {
            $args['author__not_in'] = $exclude_by_authors;
        }

        if (!empty($order_by)) {
            $args['orderby'] = $order_by;
            $args['order']   = $order;
        }
	// Filter kategori
if (!empty($_GET['course_category'])) {
    $args['tax_query'][] = array(
        'taxonomy' => 'course-category',
        'field'    => 'term_id',
        'terms'    => intval($_GET['course_category']),
    );
}

// Filter difficulty level
if (!empty($_GET['course_level'])) {
    $args['tax_query'][] = array(
        'taxonomy' => 'difficulty_level',
        'field'    => 'slug',
        'terms'    => sanitize_text_field($_GET['course_level']),
    );
}

// Filter tahun (publish year)
if (!empty($_GET['course_year'])) {
    $args['date_query'][] = array(
        'year' => intval($_GET['course_year']),
    );
}


        $the_query = new WP_Query($args);

        do_action('tutor_elementor/before/course_list');

        if ($the_query->have_posts()) :
            $courseColumns = isset($settings['course_list_column']) ? (int) $settings['course_list_column'] : 3;
            $listStyle = ($settings['course_list_masonry'] === 'yes') ? 'masonry' : 'tutor-courses';
            $layout = isset($settings['course_list_skin']) ? $settings['course_list_skin'] : 'card';
            $path = $courseColumns > 1 ? 'list' : 'list/grid';
        ?>
            <div class="etlms-course-list-loop-wrap tutor-course-list tutor-grid tutor-grid-<?php echo esc_attr($courseColumns); ?>">
                <?php while ($the_query->have_posts()) : $the_query->the_post(); ?>
                    <div class="etlms-course-list-col">
                        <?php include etlms_get_template('course/' . $path . '/' . $layout); ?>
                    </div>
                <?php endwhile; ?>
            </div>

            <?php if ($settings['course_list_pagination_settings'] === 'yes') : ?>
                <?php
                $pagination_type = $settings['course_list_pagination_type'];
                $pagination_page_limit = $settings['course_list_pagination_page_limit'];
                $pagination_prev_label = $settings['course_list_pagination_previous_label'];
                $pagination_next_label = $settings['course_list_pagination_next_label'];

                $pagination_links = paginate_links(array(
                    'format'    => '?current_page=%#%',
                    'current'   => max(1, $paged),
                    'end_size'  => $pagination_page_limit,
                    'prev_next' => $pagination_type === 'prev_next',
                    'prev_text' => esc_html__($pagination_prev_label, 'tutor-lms-elementor-addons'),
                    'next_text' => esc_html__($pagination_next_label, 'tutor-lms-elementor-addons'),
                    'total'     => $the_query->max_num_pages,
                ));
                ?>
                <div class="etlms-course-list-pagination-wrap tutor-mt-32">
                    <div class="etlms-pagination <?php echo esc_attr($pagination_type === 'prev_next' ? 'prev-next' : 'pagination-numbers-prev-next'); ?>">
                        <?php echo $pagination_links; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php
        else :
            tutor_load_template('course-none');
        endif;

        do_action('tutor_elementor/after/course_list');
        wp_reset_postdata();
        ?>
    </div>
</div>

<?php if (!is_user_logged_in()) {
    tutor_load_template_from_custom_path(tutor()->path . '/views/modal/login.php', false);
} ?>

<!-- Toggle Sidebar Script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('toggle-course-filter');
        const sidebar = document.getElementById('course-filter-sidebar');

        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
    });
</script>

<!-- CSS for Layout -->
<style>
    .etlms-course-list-container {
        display: flex;
        gap: 30px;
    }

    .course-filter-sidebar {
        width: 250px;
        background: #f8f8f8;
        padding: 20px;
        border-radius: 12px;
    }

    .etlms-course-list-content {
        flex: 1;
    }

    .mobile-filter-button {
        display: none;
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 10px 20px;
        background: #0073aa;
        color: #fff;
        border-radius: 8px;
        z-index: 1000;
    }

    @media (max-width: 768px) {
        .etlms-course-list-container {
            flex-direction: column;
        }

        .course-filter-sidebar {
            display: none;
            position: fixed;
            top: 0;
            width: 80%;
            height: 100%;
            background: white;
            z-index: 1001;
            overflow-y: auto;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
        }

        .course-filter-sidebar.open {
            display: block;
            animation: slideIn 0.3s ease-in-out;
        }

        .mobile-filter-button {
            display: block;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
    }
</style>
