#!/bin/bash
export $(grep -v '^#' ../.env | xargs)

TARGET_FILE="../public/.htaccess"
SEARCH_STRING="RewriteBase /app_code"
REPLACE_STRING="RewriteBase /${APP_CODE}"

if [ -f "$TARGET_FILE" ]; then
    
    sed -i "s|$SEARCH_STRING|$REPLACE_STRING|g" "$TARGET_FILE"
    echo "Updated $TARGET_FILE"
else
    echo "File $TARGET_FILE does not exist. Skipping."
fi

TARGET_FILE="../app/Guards/MySessionGuard.php"
SEARCH_STRING="return 'remember_app_code'"
REPLACE_STRING="return 'remember_${APP_CODE}'"

if [ -f "$TARGET_FILE" ]; then
    
    sed -i "s|$SEARCH_STRING|$REPLACE_STRING|g" "$TARGET_FILE"
    echo "Updated $TARGET_FILE"
else
    echo "File $TARGET_FILE does not exist. Skipping."
fi
