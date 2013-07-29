<?php

namespace WP_DPP\Weekly_Notification;

interface Notifier {
	public function notify();
	public function render_field_setting();
}
