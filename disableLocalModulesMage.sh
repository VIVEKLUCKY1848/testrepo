for filename in *.xml; do grep "<codePool>local" $filename;
	if [[ $filename != *"Mage_"* ]]; then
		# echo "$filename";
		sed -i.bak 's/true/false/g' "$filename"
	fi
done

## One liner of above block ##
for filename in *.xml; do grep "<codePool>local" $filename; if [[ $filename != *"Mage_"* ]]; then sed -i.bak 's/true/false/g' "$filename"; fi; done
