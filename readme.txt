=== Post Sync + On site Translation ===
Contributors: yourname
Tags: sync, translation, rest-api, chatgpt
Requires at least: 5.8
Tested up to: 6.9
Stable Tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Syncs posts from a Host site to one or more Target sites using REST APIs with key-based auth. Translation happens *on the Target* using ChatGPT.

== Description ==

Post Sync allows you to automatically push content from a main Host site to multiple Target sites. It utilizes secure key-based authentication.

== Features ==


- **Real-time Sync**: Pushes content immediately on save/update from Host to Targets.
- **Secure Auth**: Uses HMAC signing and domain binding to ensure requests are authentic and from the correct source.
- **On-site Translation**: Target site handles the translation using its own OpenAI Key.
- **Chunked Processing**: Handles large posts safely by splitting content into chunks for translation to avoid API limits.
- **Audit Logging**: Keeps a detailed log of all sync and translation actions in a custom database table.

## Installation

1. Upload the `post-sync` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

## Configuration

### Host Site
1. Go to **Settings > Post Sync**.
2. Select **Mode: Host**.
3. Add Target sites by entering their **URL** (e.g., `https://target-site.com`).
4. Click **Save Changes**.
5. Copy the generated **Key** for each target. You will need to paste this on the respective Target site.

### Target Site
1. Go to **Settings > Post Sync**.
2. Select **Mode: Target**.
3. Paste the **Connection Key** you copied from the Host.
4. Select the **Translation Language** (French, Spanish, Hindi).
5. Enter your **ChatGPT API Key**.
6. Click **Save Changes**.

## How Real-Time Push Works
When a post is published or updated on the Host, the plugin immediately sends a secure REST API request to all configured Targets. The Target validates the request, saves the post (initially in English), and then triggers a background job to translate the content. This ensures the Host is not blocked by slow translation processes.

## Limits & Known Issues
- Currently supports `post` post type only.
- Translation uses OpenAI `gpt-3.5-turbo`. A valid API key with credits is required.
- Very large posts (>50k chars) might hit PHP execution limits during chunking/reassembly depending on server stats, though the process is designed to be efficient.

## Usage
Simply publish or update a post on the Host site. Check the **Post Sync** logs (custom DB table `wp_ps_logs`) or the standard WordPress post list on the Target site to see the results.
