for filename in *.xml; do grep "<codePool>community" $filename;
	if [[ $filename != *"Mage_"* ]]; then
		# echo "$filename";
		sed -i.bak 's/true/false/g' "$filename"
	fi
done
