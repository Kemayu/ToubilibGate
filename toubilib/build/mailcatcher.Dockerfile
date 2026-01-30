FROM ruby:3.3

WORKDIR /usr/src/app

RUN gem install sqlite3
RUN gem install mailcatcher

EXPOSE 1025 1080

CMD ["mailcatcher", "-f", "--ip", "0.0.0.0"]
