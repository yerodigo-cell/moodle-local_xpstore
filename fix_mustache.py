import os
import glob

# Ensure a basic example context is present
json_context = """
    Example context (json):
    {
        "cpstore": "#0056D2",
        "cbstore": "#00C9A7",
        "cistore": "#ff9800",
        "ccstore": "#ffffff",
        "actionurl": "https://moodle.org",
        "showbackbutton": true,
        "isteacher": false,
        "saldo": 100,
        "storecategories": []
    }
"""

for filepath in glob.glob('/Users/yeisondiaz/Downloads/xpstore/templates/*.mustache'):
    with open(filepath, 'r') as f:
        content = f.read()
    
    filename = os.path.basename(filepath)
    template_name = filename.replace('.mustache', '')
    
    if content.startswith('{{!'):
        # Has boilerplate, replace closing tags
        content = content.replace('\n}}', f'\n{json_context}}}')
    else:
        # No boilerplate
        boilerplate = f"{{{{\n    @template local_xpstore/{template_name}\n{json_context}\n}}}}\n"
        content = boilerplate + content

    with open(filepath, 'w') as f:
        f.write(content)

