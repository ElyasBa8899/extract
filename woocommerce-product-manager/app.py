from flask import Flask
from woocommerce import API

app = Flask(__name__)

# WooCommerce API credentials
wcapi = API(
    url="https://tahrirchishop.com/",
    consumer_key="ck_4580190401849ee1e7fed019426235cc1c423284",
    consumer_secret="cs_fe19093d4cd1bb4c5c848584981f685a5ccc48d4",
    version="wc/v3"
)

from flask import render_template

@app.route('/')
def product_list():
    products = wcapi.get("products").json()
    return render_template('index.html', products=products)

from flask import request, redirect

@app.route('/edit/<int:product_id>')
def edit_product(product_id):
    product = wcapi.get(f"products/{product_id}").json()
    variations = wcapi.get(f"products/{product_id}/variations").json()
    return render_template('edit_product.html', product=product, variations=variations)

@app.route('/update/<int:product_id>', methods=['POST'])
def update_product(product_id):
    data = {
        "name": request.form['name'],
        "regular_price": request.form['price'],
        "description": request.form['description']
    }
    wcapi.put(f"products/{product_id}", data).json()

    for key, value in request.form.items():
        if key.startswith('variation_price_'):
            variation_id = key.replace('variation_price_', '')
            data = {
                "regular_price": value
            }
            wcapi.put(f"products/{product_id}/variations/{variation_id}", data).json()

    return redirect(f'/edit/{product_id}')

if __name__ == '__main__':
    app.run(debug=True)
