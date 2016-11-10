for filename in *.xml; do
	if [[ $filename != *"Mage_"* ]]; then
		# echo "$filename";
		sed -i.bak 's/true/false/g' "$filename"
	fi
done
