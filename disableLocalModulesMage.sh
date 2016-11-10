for filename in *.xml; do
    if grep -q '<codePool>local</codePool>' "$filename"; then
        if [[ $filename != *"Mage_"* ]]; then
            sed -i.bak 's/<active>true<\/active>/<active>false<\/active>/g' "$filename"
        fi
    fi
done
