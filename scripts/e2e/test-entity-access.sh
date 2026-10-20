#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
source "$SCRIPT_DIR/_helpers.sh"

DRUSH="${DRUSH:-drush}"
PORT=8089
BASE="http://127.0.0.1:$PORT"
JAR_DIR=$(mktemp -d)

command -v curl > /dev/null || apk add --no-cache curl > /dev/null

section "report routes follow entity view access"

$DRUSH en analyze_plugin_example --yes --quiet

nodes=$($DRUSH php:eval '
  if (!\Drupal\node\Entity\NodeType::load("article")) {
    \Drupal\node\Entity\NodeType::create(["type" => "article", "name" => "Article"])->save();
  }
  \Drupal::configFactory()->getEditable("analyze.settings")
    ->set("status.node.article.example", TRUE)->save();

  $roles = [
    "analyze_viewer" => ["access content", "view analyze reports"],
    "analyze_editor" => ["access content", "view analyze reports", "bypass node access"],
    "analyze_none" => ["access content"],
  ];
  foreach ($roles as $id => $permissions) {
    $role = \Drupal\user\Entity\Role::create(["id" => $id, "label" => $id]);
    foreach ($permissions as $permission) {
      $role->grantPermission($permission);
    }
    $role->save();
    \Drupal\user\Entity\User::create([
      "name" => $id,
      "mail" => "$id@example.com",
      "status" => 1,
      "roles" => [$id],
    ])->save();
  }

  foreach (["published" => 1, "unpublished" => 0] as $key => $status) {
    $node = \Drupal\node\Entity\Node::create([
      "type" => "article",
      "title" => "Analyze access $key",
      "status" => $status,
      "uid" => 1,
    ]);
    $node->save();
    echo "$key=" . $node->id() . "\n";
  }
')
published=$(echo "$nodes" | sed -n 's/^published=//p')
unpublished=$(echo "$nodes" | sed -n 's/^unpublished=//p')

cd "$($DRUSH status --field=root)"
php -S "127.0.0.1:$PORT" -t . .ht.router.php > /dev/null 2>&1 &
SERVER_PID=$!
trap 'kill $SERVER_PID 2> /dev/null; rm -rf "$JAR_DIR"' EXIT
until (exec 3<> "/dev/tcp/127.0.0.1/$PORT") 2> /dev/null; do sleep 0.2; done

login() {
  curl -s -L -c "$JAR_DIR/$1" -o /dev/null \
    "$($DRUSH user:login --name="$1" --uri="$BASE")"
}

status() {
  curl -s -o /dev/null -w '%{http_code}' -b "$JAR_DIR/$1" "$BASE$2"
}

login analyze_viewer
login analyze_editor
login analyze_none

assert_has "viewer sees the report of a published node" \
  "200" "$(status analyze_viewer "/node/$published/analyze")"
assert_has "viewer sees the full report of a published node" \
  "200" "$(status analyze_viewer "/node/$published/analyze/example")"
assert_has "viewer cannot view the unpublished node" \
  "403" "$(status analyze_viewer "/node/$unpublished")"
assert_has "viewer is denied the report of an unpublished node" \
  "403" "$(status analyze_viewer "/node/$unpublished/analyze")"
assert_has "viewer is denied the full report of an unpublished node" \
  "403" "$(status analyze_viewer "/node/$unpublished/analyze/example")"
assert_has "editor sees the report of an unpublished node" \
  "200" "$(status analyze_editor "/node/$unpublished/analyze")"
assert_has "user without the permission is denied a published node" \
  "403" "$(status analyze_none "/node/$published/analyze")"

print_summary
