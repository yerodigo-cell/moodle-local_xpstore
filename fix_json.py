import os
import glob
import re

for filepath in glob.glob('/Users/yeisondiaz/Downloads/xpstore/templates/*.mustache'):
    with open(filepath, 'r') as f:
        content = f.read()
    
    content = content.replace('"storecategories": []', '"storecategories": [], "tipos": [{"value": "1", "label": "dummy"}], "activities": [{"id": 1, "name": "dummy"}]')

    with open(filepath, 'w') as f:
        f.write(content)

